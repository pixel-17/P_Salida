<?php

namespace App\Actions;

use App\Enums\EstadoPapeleta;
use App\Models\Estado;
use App\Models\HistorialPapeleta;
use App\Models\Papeleta;
use App\Models\User;
use App\Notifications\PapeletaSolicitadaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrearPapeletaAction
{
    public function execute(User $trabajador, array $datos): Papeleta
    {
        $papeleta = DB::transaction(function () use ($trabajador, $datos) {
            $papeleta = Papeleta::create([
                'codigo' => $this->generarCodigo(),
                'trabajador_id' => $trabajador->id,
                'jefe_id' => $trabajador->jefe_id,
                'area_id' => $trabajador->cargo?->area_id,
                'sede_id' => $trabajador->sede_id,
                'motivo_id' => $datos['motivo_id'],
                'estado_id' => Estado::porCodigo(EstadoPapeleta::SOLICITADO)->id,
                'destino' => $datos['destino'],
                'motivo_detalle' => $datos['motivo_detalle'] ?? null,
                'fecha_salida' => $datos['fecha_salida'],
                'hora_salida_programada' => $datos['hora_salida_programada'],
                'hora_retorno_programada' => $datos['hora_retorno_programada'] ?? null,
            ]);

            HistorialPapeleta::registrar(
                $papeleta, $trabajador, 'CREADA', null, EstadoPapeleta::SOLICITADO->value,
                'Papeleta creada por el trabajador'
            );

            return $papeleta;
        });

        if ($papeleta->jefe) {
            $papeleta->jefe->notify(new PapeletaSolicitadaNotification($papeleta));
        }

        return $papeleta;
    }

    private function generarCodigo(): string
    {
        $anio = now()->year;
        $ultimo = Papeleta::whereYear('created_at', $anio)->count() + 1;

        return sprintf('PAP-%d-%05d-%s', $anio, $ultimo, Str::upper(Str::random(3)));
    }
}
