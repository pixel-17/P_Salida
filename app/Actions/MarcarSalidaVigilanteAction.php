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
use App\Notifications\MarcacionSalidaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
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

        $horaLimite = Configuracion::obtener('hora_limite_registro_garita', '17:00');

        if ($horaLimite && now()->greaterThan(today()->setTimeFromTimeString($horaLimite))) {
            throw ValidationException::withMessages([
                'marcacion' => "Ya pasó el horario límite para registrar salidas ({$horaLimite}).",
            ]);
        }

        // La fecha autorizada no es negociable: la hora programada es solo
        // un estimado (el trabajador puede salir antes o después dentro del
        // mismo día), pero el DÍA sí es fijo. PapeletaPolicy::marcarComoVigilante
        // es compartida con el retorno (que no debe llevar esta restricción)
        // y no valida fecha, así que esta es la barrera real y única contra
        // marcar salida en un día distinto al autorizado.
        if (! $papeleta->esHoyFechaDeSalida()) {
            $dias = $papeleta->diasParaSalida();
            $fecha = $papeleta->fecha_salida->format('d/m/Y');

            throw ValidationException::withMessages([
                'marcacion' => $dias > 0
                    ? "Esta papeleta es para el {$fecha} — faltan {$dias} ".Str::plural('día', $dias).". No se puede marcar salida antes de la fecha autorizada."
                    : "La fecha autorizada para esta papeleta ({$fecha}) ya pasó.",
            ]);
        }

        DB::transaction(function () use ($papeleta, $vigilante) {
            // Lock de la fila: dos escaneos casi simultáneos del mismo QR (dos
            // dispositivos, o doble tap) ya no dependen únicamente del UNIQUE
            // de `marcaciones` (que igual sigue ahí como última barrera) —
            // el segundo espera el lock, relee y encuentra yaMarcoSalida()
            // en true, así que sale con el mismo mensaje de validación de
            // siempre en vez de un 500 por QueryException.
            $papeletaLock = Papeleta::whereKey($papeleta->id)->lockForUpdate()->first();
            $papeletaLock->load('marcaciones');

            if ($papeletaLock->yaMarcoSalida()) {
                throw ValidationException::withMessages([
                    'marcacion' => 'Esta papeleta ya tiene una marcación de salida registrada.',
                ]);
            }

            Marcacion::create([
                'papeleta_id' => $papeletaLock->id,
                'tipo' => TipoMarcacion::SALIDA->value,
                'registrado_por_user_id' => $vigilante->id,
            ]);

            $estadoAnterior = $papeletaLock->estado->codigo;
            $papeletaLock->update(['estado_id' => Estado::porCodigo(EstadoPapeleta::EN_CURSO)->id]);

            HistorialPapeleta::registrar(
                $papeletaLock, $vigilante, 'MARCO_SALIDA_VIGILANTE', $estadoAnterior, EstadoPapeleta::EN_CURSO->value,
                "Confirmado por vigilante: {$vigilante->name}"
            );
        });

        $papeleta->refresh();
        $papeleta->jefe?->notify(new MarcacionSalidaNotification($papeleta));

        return $papeleta;
    }
}
