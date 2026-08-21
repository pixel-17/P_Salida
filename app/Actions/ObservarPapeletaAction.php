<?php

namespace App\Actions;

use App\Enums\AccionFlujo;
use App\Enums\EstadoPapeleta;
use App\Enums\RolUsuario;
use App\Enums\TipoObservacion;
use App\Models\Estado;
use App\Models\FlujoAprobacion;
use App\Models\HistorialPapeleta;
use App\Models\Observacion;
use App\Models\Papeleta;
use App\Models\User;
use App\Notifications\PapeletaObservadaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ObservarPapeletaAction
{
    public function execute(
        Papeleta $papeleta,
        User $usuario,
        string $comentario,
        TipoObservacion $tipo,
    ): Papeleta {
        Gate::forUser($usuario)->authorize('decidir', $papeleta);

        // Mismo caso que en RechazarPapeletaAction: $usuario->rol no existe.
        $rolActuando = $usuario->hasRole(RolUsuario::JEFE) ? RolUsuario::JEFE : RolUsuario::RRHH;

        DB::transaction(function () use ($papeleta, $usuario, $comentario, $tipo, $rolActuando) {
            // Lock + re-chequeo de autorización contra el estado real: sin
            // esto, dos decisiones simultáneas sobre la misma papeleta
            // (ej. observar en una pestaña y rechazar en otra) podían
            // pisarse sin error visible. Mismo patrón que AprobarPapeletaAction.
            $papeletaLock = Papeleta::whereKey($papeleta->id)->lockForUpdate()->first();

            Gate::forUser($usuario)->authorize('decidir', $papeletaLock);

            $estadoAnteriorCodigo = $papeletaLock->estado->codigo;

            $papeletaLock->update(['estado_id' => Estado::porCodigo(EstadoPapeleta::OBSERVADO)->id]);

            Observacion::create([
                'papeleta_id' => $papeletaLock->id,
                'usuario_id' => $usuario->id,
                'tipo' => $tipo->value,
                'comentario' => $comentario,
            ]);

            FlujoAprobacion::create([
                'papeleta_id' => $papeletaLock->id,
                'usuario_id' => $usuario->id,
                'rol' => $rolActuando->value,
                'accion' => AccionFlujo::OBSERVADO->value,
                'comentario' => $comentario,
            ]);

            HistorialPapeleta::registrar(
                $papeletaLock, $usuario, 'OBSERVADA', $estadoAnteriorCodigo, EstadoPapeleta::OBSERVADO->value, $comentario
            );
        });

        $papeleta->refresh();
        $papeleta->trabajador->notify(new PapeletaObservadaNotification($papeleta, $comentario));

        return $papeleta;
    }
}
