<?php

namespace App\Actions;

use App\Enums\EstadoPapeleta;
use App\Enums\TipoMarcacion;
use App\Models\Configuracion;
use App\Models\Estado;
use App\Models\HistorialPapeleta;
use App\Models\Marcacion;
use App\Models\Papeleta;
use App\Models\User;
use App\Notifications\PapeletaFinalizadaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Retorno confirmado por el vigilante: es la única vía que existe — el
 * vigilante YA ES la verificación física, así que finaliza directo, sin
 * paso intermedio de confirmación del jefe.
 */
class MarcarRetornoVigilanteAction
{
    public function execute(Papeleta $papeleta, User $vigilante): Papeleta
    {
        Gate::forUser($vigilante)->authorize('marcarComoVigilante', $papeleta);

        $horaLimite = Configuracion::horaLimiteGarita();

        if ($horaLimite && now()->greaterThan(today()->setTimeFromTimeString($horaLimite))) {
            throw ValidationException::withMessages([
                'marcacion' => "Ya pasó el horario límite para registrar retornos ({$horaLimite}).",
            ]);
        }

        DB::transaction(function () use ($papeleta, $vigilante) {
            // Mismo criterio que en MarcarSalidaVigilanteAction: lock de la
            // fila para que un doble escaneo casi simultáneo del retorno no
            // dependa solo del UNIQUE de `marcaciones` y termine en 500.
            $papeletaLock = Papeleta::whereKey($papeleta->id)->lockForUpdate()->first();
            $papeletaLock->load('marcaciones');

            if (! $papeletaLock->yaMarcoSalida()) {
                throw ValidationException::withMessages([
                    'marcacion' => 'No se puede marcar retorno sin una salida registrada.',
                ]);
            }

            if ($papeletaLock->yaMarcoRetorno()) {
                throw ValidationException::withMessages([
                    'marcacion' => 'Esta papeleta ya tiene una marcación de retorno registrada.',
                ]);
            }

            Marcacion::create([
                'papeleta_id' => $papeletaLock->id,
                'tipo' => TipoMarcacion::RETORNO->value,
                'registrado_por_user_id' => $vigilante->id,
            ]);

            $estadoAnterior = $papeletaLock->estado->codigo;
            $papeletaLock->update(['estado_id' => Estado::porCodigo(EstadoPapeleta::FINALIZADO)->id]);

            HistorialPapeleta::registrar(
                $papeletaLock, $vigilante, 'MARCO_RETORNO_VIGILANTE', $estadoAnterior, EstadoPapeleta::FINALIZADO->value,
                "Confirmado por vigilante: {$vigilante->name}"
            );
        });

        $papeleta->refresh();

        // Único correo de constancia de todo el proceso, ver
        // PapeletaFinalizadaNotification.
        $papeleta->trabajador->notify(new PapeletaFinalizadaNotification($papeleta));

        return $papeleta;
    }
}
