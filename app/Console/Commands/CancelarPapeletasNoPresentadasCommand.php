<?php

namespace App\Console\Commands;

use App\Enums\EstadoPapeleta;
use App\Models\Estado;
use App\Models\HistorialPapeleta;
use App\Models\Papeleta;
use App\Notifications\PapeletaCanceladaNotification;
use Illuminate\Console\Command;

/**
 * Cierre definitivo de fin de día: toda papeleta cuya fecha_salida ya pasó
 * y que se quedó "en trámite" (nunca llegó a EN_CURSO ni a un estado
 * terminal) se cancela automáticamente, sin importar en qué paso del
 * flujo de aprobación se haya quedado — SOLICITADO (nunca la vio el
 * jefe), APROBADO_JEFE (nunca la vio RRHH), APROBADO_RRHH (aprobada pero
 * el trabajador nunca marcó salida) u OBSERVADO (quedó esperando
 * respuesta del trabajador). Antes solo cubría APROBADO_RRHH.
 *
 * EN_CURSO queda fuera a propósito: esa papeleta ya tiene al trabajador
 * afuera, y su cierre por tiempo lo maneja papeletas:marcar-vencidas (que
 * usa el plazo de retorno real, no solo la fecha), para no perder esa
 * distinción de "salió y no volvió a tiempo" vs. "nunca se presentó".
 *
 * Pensado para correr una vez al día, cerca de medianoche — ver
 * routes/console.php.
 */
class CancelarPapeletasNoPresentadasCommand extends Command
{
    protected $signature = 'papeletas:cancelar-no-presentadas';

    protected $description = 'Cancela toda papeleta con fecha de salida vencida que se quedó en trámite, sin importar la etapa';

    /**
     * Estados "en trámite": cualquiera de estos con fecha_salida vencida se
     * cancela. Deliberadamente NO incluye EN_CURSO ni los ya terminales
     * (FINALIZADO, RECHAZADO, CANCELADO, VENCIDA).
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

        $estadoCancelado = Estado::porCodigo(EstadoPapeleta::CANCELADO);

        $papeletas = Papeleta::whereIn('estado_id', $idsEnTramite)
            ->whereDate('fecha_salida', '<=', now()->toDateString())
            ->with(['trabajador', 'jefe', 'estado'])
            ->get();

        $canceladas = 0;

        foreach ($papeletas as $papeleta) {
            $estadoAnteriorCodigo = $papeleta->estado->codigo;

            $papeleta->update(['estado_id' => $estadoCancelado->id]);

            HistorialPapeleta::registrar(
                $papeleta,
                null,
                'CANCELADA_AUTOMATICA',
                $estadoAnteriorCodigo,
                EstadoPapeleta::CANCELADO->value,
                "Cancelada automáticamente por papeletas:cancelar-no-presentadas: la fecha de salida venció estando en {$estadoAnteriorCodigo}."
            );

            $papeleta->trabajador->notify(new PapeletaCanceladaNotification($papeleta));
            $papeleta->jefe?->notify(new PapeletaCanceladaNotification($papeleta));

            $canceladas++;
        }

        $this->info("Papeletas canceladas por fecha vencida: {$canceladas}");

        return self::SUCCESS;
    }
}
