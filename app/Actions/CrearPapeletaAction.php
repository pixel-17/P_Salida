<?php

namespace App\Actions;

use App\Enums\EstadoPapeleta;
use App\Models\Adjunto;
use App\Models\Estado;
use App\Models\HistorialPapeleta;
use App\Models\Papeleta;
use App\Models\User;
use App\Notifications\PapeletaSolicitadaNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrearPapeletaAction
{
    /**
     * $archivo: sustento inicial, solo cuando el motivo lo exige (ver
     * StorePapeletaRequest y Motivo::requiere_documento). Se guarda en la
     * misma transacción que la papeleta para no dejar un registro huérfano
     * si algo falla a medio camino.
     */
    public function execute(User $trabajador, array $datos, ?UploadedFile $archivo = null): Papeleta
    {
        // retry(): si el código chocara con el UNIQUE de BD (ver
        // generarCodigo()), se reintenta con un código nuevo en vez de
        // devolver un 500 al trabajador por una colisión de baja
        // probabilidad. 3 intentos es de sobra dado el espacio del sufijo.
        $papeleta = retry(3, function () use ($trabajador, $datos, $archivo) {
            return $this->crear($trabajador, $datos, $archivo);
        });

        if ($papeleta->jefe) {
            $papeleta->jefe->notify(new PapeletaSolicitadaNotification($papeleta));
        }

        return $papeleta;
    }

    private function crear(User $trabajador, array $datos, ?UploadedFile $archivo): Papeleta
    {
        return DB::transaction(function () use ($trabajador, $datos, $archivo) {
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

            if ($archivo) {
                $ruta = $archivo->store("papeletas/{$papeleta->id}", 'local');

                Adjunto::create([
                    'papeleta_id' => $papeleta->id,
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'archivo' => $ruta,
                    'extension' => $archivo->getClientOriginalExtension(),
                    'peso' => $archivo->getSize(),
                ]);
            }

            HistorialPapeleta::registrar(
                $papeleta, $trabajador, 'CREADA', null, EstadoPapeleta::SOLICITADO->value,
                'Papeleta creada por el trabajador'
            );

            return $papeleta;
        });
    }

    /**
     * El contador (count()+1) no es atómico bajo concurrencia real: dos
     * solicitudes casi simultáneas pueden calcular el mismo $ultimo. El
     * sufijo aleatorio de 4 caracteres reduce la probabilidad de choque,
     * pero no la elimina — así que generarCodigoUnico() reintenta si el
     * UNIQUE de BD (migración de papeletas) rechaza el insert, en vez de
     * dejar que el usuario vea un 500 por un choque de baja probabilidad.
     */
    private function generarCodigo(): string
    {
        $anio = now()->year;
        $ultimo = Papeleta::whereYear('created_at', $anio)->count() + 1;

        return sprintf('PAP-%d-%05d-%s', $anio, $ultimo, Str::upper(Str::random(4)));
    }
}
