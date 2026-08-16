<?php

namespace App\Console\Commands;

use App\Enums\EstadoPapeleta;
use App\Models\Estado;
use App\Models\HistorialPapeleta;
use App\Models\Papeleta;
use App\Notifications\PapeletaSalidaNoMarcadaNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Alerta temprana (sin cerrar nada): a los 30 min de pasada la hora de
 * salida programada, si el vigilante no marcó salida, avisa a trabajador y
 * jefe. El cierre definitivo (VENCIDA) es un comando aparte, a fin de día
 * — ver CancelarPapeletasNoPresentadasCommand.
 */
class AlertarSalidaNoMarcadaCommand extends Command
{
    protected $signature = 'papeletas:alertar-salida-no-marcada';

    protected $description = 'Avisa (sin cerrar) cuando pasaron 30 min de la hora de salida y no se marcó';

    private const MINUTOS_GRACIA = 30;

    public function handle(): int
    {
        $estadoAprobadoRrhh = Estado::porCodigo(EstadoPapeleta::APROBADO_RRHH);

        $papeletas = Papeleta::where('estado_id', $estadoAprobadoRrhh->id)
            ->whereNull('alerta_salida_enviada_at')
            ->with(['trabajador', 'jefe'])
            ->get();

        $alertadas = 0;

        foreach ($papeletas as $papeleta) {
            $limite = Carbon::parse($papeleta->fecha_salida->format('Y-m-d').' '.$papeleta->hora_salida_programada)
                ->addMinutes(self::MINUTOS_GRACIA);

            if (now()->lessThan($limite)) {
                continue;
            }

            $papeleta->forceFill(['alerta_salida_enviada_at' => now()])->save();

            HistorialPapeleta::registrar(
                $papeleta,
                null,
                'ALERTA_SALIDA_NO_MARCADA',
                EstadoPapeleta::APROBADO_RRHH->value,
                EstadoPapeleta::APROBADO_RRHH->value,
                'Alerta automática: pasaron '.self::MINUTOS_GRACIA.' min de la hora de salida sin marcación.'
            );

            $papeleta->trabajador->notify(new PapeletaSalidaNoMarcadaNotification($papeleta));
            $papeleta->jefe?->notify(new PapeletaSalidaNoMarcadaNotification($papeleta));

            $alertadas++;
        }

        $this->info("Papeletas alertadas por salida no marcada: {$alertadas}");

        return self::SUCCESS;
    }
}
