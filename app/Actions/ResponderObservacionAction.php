<?php

namespace App\Actions;

use App\Enums\EstadoPapeleta;
use App\Enums\RolUsuario;
use App\Models\Estado;
use App\Models\HistorialPapeleta;
use App\Models\Papeleta;
use App\Models\User;
use App\Notifications\ObservacionRespondidaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ResponderObservacionAction
{
    public function execute(Papeleta $papeleta, User $trabajador, string $respuesta): Papeleta
    {
        Gate::forUser($trabajador)->authorize('responderObservacion', $papeleta);

        $observacion = $papeleta->observaciones()->where('atendida', false)->latest()->first();

        if (! $observacion) {
            throw ValidationException::withMessages([
                'respuesta' => 'No hay una observación pendiente por responder.',
            ]);
        }

        // El tipo (ADMINISTRATIVA/JUSTIFICACION) NO determina a dónde vuelve
        // la papeleta: eso mandaba siempre la ADMINISTRATIVA directo a RRHH,
        // aunque la hubiera levantado el Jefe (estado SOLICITADO), saltándose
        // su aprobación. Lo correcto es volver al estado desde el que se
        // observó, que quedó registrado en el historial al crear la
        // observación (ver ObservarPapeletaAction).
        $historialObservacion = $papeleta->historial()
            ->where('accion', 'OBSERVADA')
            ->latest()
            ->first();

        $estadoDestino = $historialObservacion?->estado_anterior === EstadoPapeleta::APROBADO_JEFE->value
            ? EstadoPapeleta::APROBADO_JEFE
            : EstadoPapeleta::SOLICITADO;

        DB::transaction(function () use ($papeleta, $trabajador, $respuesta, $observacion, $estadoDestino) {
            $observacion->update(['atendida' => true]);

            $papeleta->update(['estado_id' => Estado::porCodigo($estadoDestino)->id]);

            HistorialPapeleta::registrar(
                $papeleta, $trabajador, 'RESPONDIO_OBSERVACION',
                EstadoPapeleta::OBSERVADO->value, $estadoDestino->value, $respuesta
            );
        });

        $papeleta->refresh();

        // Se notifica a quien le toca decidir a continuación: el jefe si
        // vuelve a SOLICITADO, o RRHH (todo el rol) si vuelve a APROBADO_JEFE.
        if ($estadoDestino === EstadoPapeleta::SOLICITADO) {
            $papeleta->jefe?->notify(new ObservacionRespondidaNotification($papeleta, $respuesta));
        } else {
            Notification::send(
                User::role(RolUsuario::RRHH->value)->get(),
                new ObservacionRespondidaNotification($papeleta, $respuesta)
            );
        }

        return $papeleta;
    }
}
