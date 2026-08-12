<?php

namespace App\Console\Commands;

use App\Enums\EstadoPapeleta;
use App\Enums\TipoMarcacion;
use App\Models\Estado;
use App\Models\HistorialPapeleta;
use App\Models\Papeleta;
use App\Notifications\PapeletaVencidaNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MarcarPapeletasVencidasCommand extends Command
{
    protected $signature = 'papeletas:marcar-vencidas';

    protected $description = 'Marca como VENCIDA toda papeleta EN_CURSO cuyo plazo de retorno ya pasó';

    public function handle(): int
    {
        $estadoEnCurso = Estado::porCodigo(EstadoPapeleta::EN_CURSO);
        $estadoVencida = Estado::porCodigo(EstadoPapeleta::VENCIDA);

        $papeletas = Papeleta::where('estado_id', $estadoEnCurso->id)
            ->with(['motivo', 'marcaciones', 'trabajador', 'jefe'])
            ->get();

        $vencidas = 0;

        foreach ($papeletas as $papeleta) {
            $limite = $this->calcularLimite($papeleta);

            if ($limite === null || now()->lessThan($limite)) {
                continue;
            }

            $papeleta->update(['estado_id' => $estadoVencida->id]);

            HistorialPapeleta::registrar(
                $papeleta,
                null,
                'VENCIDA_AUTOMATICA',
                EstadoPapeleta::EN_CURSO->value,
                EstadoPapeleta::VENCIDA->value,
                'Marcada automáticamente por papeletas:marcar-vencidas'
            );

            $papeleta->trabajador->notify(new PapeletaVencidaNotification($papeleta));
            $papeleta->jefe?->notify(new PapeletaVencidaNotification($papeleta));

            $vencidas++;
        }

        $this->info("Papeletas marcadas como vencidas: {$vencidas}");

        return self::SUCCESS;
    }

    /**
     * El plazo real es hora_retorno_programada si se definió; si no,
     * cae en motivo.max_horas contado desde la marcación de salida.
     * Si ninguno de los dos existe, la papeleta no vence automáticamente.
     */
    private function calcularLimite(Papeleta $papeleta): ?Carbon
    {
        if ($papeleta->hora_retorno_programada) {
            return Carbon::parse($papeleta->fecha_salida->format('Y-m-d').' '.$papeleta->hora_retorno_programada);
        }

        $marcacionSalida = $papeleta->marcaciones->firstWhere('tipo', TipoMarcacion::SALIDA->value);

        if ($marcacionSalida && $papeleta->motivo->max_horas) {
            return $marcacionSalida->created_at->addHours($papeleta->motivo->max_horas);
        }

        return null;
    }
}
