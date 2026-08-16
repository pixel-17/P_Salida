<?php

namespace App\Console\Commands;

use App\Enums\EstadoPapeleta;
use App\Models\Estado;
use App\Models\HistorialPapeleta;
use App\Models\Papeleta;
use App\Notifications\PapeletaVencidaNotification;
use Illuminate\Console\Command;

/**
 * Cierre definitivo de fin de día: toda papeleta cuya fecha_salida ya pasó
 * y que se quedó "en trámite" (nunca llegó a EN_CURSO ni a un estado
 * terminal) se marca VENCIDA automáticamente, sin importar en qué paso del
 * flujo de aprobación se haya quedado — SOLICITADO (nunca la vio el
 * jefe), APROBADO_JEFE (nunca la vio RRHH), APROBADO_RRHH (aprobada pero
 * el trabajador nunca marcó salida) u OBSERVADO (quedó esperando
 * respuesta del trabajador).
 *
 * IMPORTANTE: esto YA NO cancela nada. CANCELADO quedó reservado
 * exclusivamente para cuando el propio trabajador cancela su papeleta
 * desde su usuario (ver PapeletaController::cancelar). Cualquier cierre
 * automático por vencimiento —incluido este, "nunca se presentó"— cae en
 * VENCIDA. La etiqueta que se muestra ("No se presentó" vs "Sin retorno")
 * se calcula sola según si llegó a marcar salida, ver
 * Papeleta::etiquetaVencimiento().
 *
 * EN_CURSO queda fuera a propósito: esa papeleta ya tiene al trabajador
 * afuera, y su cierre por tiempo lo maneja papeletas:marcar-vencidas (que
 * usa el plazo de retorno real, no solo la fecha).
 *
 * Pensado para correr una vez al día, cerca de medianoche — ver
 * routes/console.php.
 */
class CancelarPapeletasNoPresentadasCommand extends Command
{
    protected $signature = 'papeletas:cancelar-no-presentadas';

    protected $description = 'Marca como VENCIDA toda papeleta con fecha de salida vencida que se quedó en trámite sin presentarse, sin importar la etapa';

    /**
     * Estados "en trámite": cualquiera de estos con fecha_salida vencida se
     * marca VENCIDA. Deliberadamente NO incluye EN_CURSO ni los ya
     * terminales (FINALIZADO, RECHAZADO, CANCELADO, VENCIDA).
     */
    private const ESTADOS_EN_TRAMITE = [
        EstadoPapeleta::SOLICITADO,
        EstadoPapeleta::APROBADO_JEFE,
        EstadoPapeleta::APROBADO_RRHH,
        EstadoPapeleta::OBSERVADO,
    ];

    public function handle(): int
    {
        $idsEnTramite = collect(self::ESTADOS_EN_TRAMITE)
            ->map(fn (EstadoPapeleta $codigo) => Estado::porCodigo($codigo)->id);

        $estadoVencida = Estado::porCodigo(EstadoPapeleta::VENCIDA);

        $papeletas = Papeleta::whereIn('estado_id', $idsEnTramite)
            ->whereDate('fecha_salida', '<=', now()->toDateString())
            ->with(['trabajador', 'jefe', 'estado'])
            ->get();

        $vencidas = 0;

        foreach ($papeletas as $papeleta) {
            $estadoAnteriorCodigo = $papeleta->estado->codigo;

            $papeleta->update(['estado_id' => $estadoVencida->id]);

            HistorialPapeleta::registrar(
                $papeleta,
                null,
                'VENCIDA_AUTOMATICA_NO_PRESENTADO',
                $estadoAnteriorCodigo,
                EstadoPapeleta::VENCIDA->value,
                "Marcada VENCIDA automáticamente por papeletas:cancelar-no-presentadas: la fecha de salida venció estando en {$estadoAnteriorCodigo} (nunca se presentó a marcar salida)."
            );

            $papeleta->trabajador->notify(new PapeletaVencidaNotification($papeleta));
            $papeleta->jefe?->notify(new PapeletaVencidaNotification($papeleta));

            $vencidas++;
        }

        $this->info("Papeletas marcadas VENCIDA por no presentarse: {$vencidas}");

        return self::SUCCESS;
    }
}
