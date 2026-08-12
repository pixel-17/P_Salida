<?php

namespace App\Console\Commands;

use App\Enums\EstadoPapeleta;
use App\Models\Estado;
use App\Models\HistorialPapeleta;
use App\Models\Papeleta;
use App\Notifications\PapeletaCanceladaNotification;
use Illuminate\Console\Command;

/**
 * Cierre definitivo de fin de día: toda papeleta APROBADO_RRHH cuya
 * fecha_salida ya pasó (o es hoy, corriendo tarde en la noche) y que nunca
 * llegó a marcar salida, se cancela. Pensado para correr una vez al día,
 * cerca de medianoche — ver routes/console.php.
 *
 * El filtro es por fecha_salida <= hoy (no solo "= hoy") a propósito: sirve
 * de red de seguridad si el comando no corrió algún día por la razón que sea.
 */
class CancelarPapeletasNoPresentadasCommand extends Command
{
    protected $signature = 'papeletas:cancelar-no-presentadas';

    protected $description = 'Cancela las papeletas aprobadas cuyo día ya terminó y nunca marcaron salida';

    public function handle(): int
    {
        $estadoAprobadoRrhh = Estado::porCodigo(EstadoPapeleta::APROBADO_RRHH);
        $estadoCancelado = Estado::porCodigo(EstadoPapeleta::CANCELADO);

        $papeletas = Papeleta::where('estado_id', $estadoAprobadoRrhh->id)
            ->whereDate('fecha_salida', '<=', now()->toDateString())
            ->with(['trabajador', 'jefe'])
            ->get();

        $canceladas = 0;

        foreach ($papeletas as $papeleta) {
            $papeleta->update(['estado_id' => $estadoCancelado->id]);

            HistorialPapeleta::registrar(
                $papeleta,
                null,
                'CANCELADA_AUTOMATICA',
                EstadoPapeleta::APROBADO_RRHH->value,
                EstadoPapeleta::CANCELADO->value,
                'Cancelada automáticamente por papeletas:cancelar-no-presentadas: terminó el día sin marcar salida.'
            );

            $papeleta->trabajador->notify(new PapeletaCanceladaNotification($papeleta));
            $papeleta->jefe?->notify(new PapeletaCanceladaNotification($papeleta));

            $canceladas++;
        }

        $this->info("Papeletas canceladas por no presentarse: {$canceladas}");

        return self::SUCCESS;
    }
}
