<?php

namespace App\Actions;

use App\Enums\EstadoPapeleta;
use App\Enums\TipoMarcacion;
use App\Models\Estado;
use App\Models\HistorialPapeleta;
use App\Models\Marcacion;
use App\Models\Papeleta;
use App\Models\User;
use App\Notifications\MarcacionSalidaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Salida confirmada por el vigilante de la puerta (QR o código), nunca por
 * el propio trabajador (ver PapeletaPolicy::marcar, que siempre es false).
 */
class MarcarSalidaVigilanteAction
{
    public function execute(Papeleta $papeleta, User $vigilante): Papeleta
    {
        Gate::forUser($vigilante)->authorize('marcarComoVigilante', $papeleta);

        if ($papeleta->yaMarcoSalida()) {
            throw ValidationException::withMessages([
                'marcacion' => 'Esta papeleta ya tiene una marcación de salida registrada.',
            ]);
        }

        DB::transaction(function () use ($papeleta, $vigilante) {
            Marcacion::create([
                'papeleta_id' => $papeleta->id,
                'tipo' => TipoMarcacion::SALIDA->value,
                'registrado_por_user_id' => $vigilante->id,
            ]);

            $estadoAnterior = $papeleta->estado->codigo;
            $papeleta->update(['estado_id' => Estado::porCodigo(EstadoPapeleta::EN_CURSO)->id]);

            HistorialPapeleta::registrar(
                $papeleta, $vigilante, 'MARCO_SALIDA_VIGILANTE', $estadoAnterior, EstadoPapeleta::EN_CURSO->value,
                "Confirmado por vigilante: {$vigilante->name}"
            );
        });

        $papeleta->refresh();
        $papeleta->jefe?->notify(new MarcacionSalidaNotification($papeleta));

        return $papeleta;
    }
}
