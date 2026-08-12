<?php

namespace App\Actions;

use App\Enums\EstadoPapeleta;
use App\Enums\TipoMarcacion;
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

        if (! $papeleta->yaMarcoSalida()) {
            throw ValidationException::withMessages([
                'marcacion' => 'No se puede marcar retorno sin una salida registrada.',
            ]);
        }

        if ($papeleta->yaMarcoRetorno()) {
            throw ValidationException::withMessages([
                'marcacion' => 'Esta papeleta ya tiene una marcación de retorno registrada.',
            ]);
        }

        DB::transaction(function () use ($papeleta, $vigilante) {
            Marcacion::create([
                'papeleta_id' => $papeleta->id,
                'tipo' => TipoMarcacion::RETORNO->value,
                'registrado_por_user_id' => $vigilante->id,
            ]);

            $estadoAnterior = $papeleta->estado->codigo;
            $papeleta->update(['estado_id' => Estado::porCodigo(EstadoPapeleta::FINALIZADO)->id]);

            HistorialPapeleta::registrar(
                $papeleta, $vigilante, 'MARCO_RETORNO_VIGILANTE', $estadoAnterior, EstadoPapeleta::FINALIZADO->value,
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
