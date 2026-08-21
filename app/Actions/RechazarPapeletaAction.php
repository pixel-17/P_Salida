<?php

namespace App\Actions;

use App\Enums\AccionFlujo;
use App\Enums\EstadoPapeleta;
use App\Enums\RolUsuario;
use App\Models\Estado;
use App\Models\FlujoAprobacion;
use App\Models\HistorialPapeleta;
use App\Models\Papeleta;
use App\Models\User;
use App\Notifications\PapeletaRechazadaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RechazarPapeletaAction
{
    public function execute(Papeleta $papeleta, User $usuario, string $comentario): Papeleta
    {
        Gate::forUser($usuario)->authorize('decidir', $papeleta);

        // $usuario->rol no existe (los roles van por Spatie, vía hasRole),
        // siempre daba null -> quedaba mal registrado como "RRHH" incluso
        // cuando rechazaba el Jefe.
        $rolActuando = $usuario->hasRole(RolUsuario::JEFE) ? RolUsuario::JEFE : RolUsuario::RRHH;

        DB::transaction(function () use ($papeleta, $usuario, $comentario, $rolActuando) {
            // Lock + re-chequeo de autorización contra el estado real: evita
            // que dos decisiones simultáneas (aprobar en una pestaña,
            // rechazar en otra) se pisen sin error visible. Mismo patrón
            // que AprobarPapeletaAction.
            $papeletaLock = Papeleta::whereKey($papeleta->id)->lockForUpdate()->first();

            Gate::forUser($usuario)->authorize('decidir', $papeletaLock);

            $estadoAnteriorCodigo = $papeletaLock->estado->codigo;

            $papeletaLock->update(['estado_id' => Estado::porCodigo(EstadoPapeleta::RECHAZADO)->id]);

            FlujoAprobacion::create([
                'papeleta_id' => $papeletaLock->id,
                'usuario_id' => $usuario->id,
                'rol' => $rolActuando->value,
                'accion' => AccionFlujo::RECHAZADO->value,
                'comentario' => $comentario,
            ]);

            HistorialPapeleta::registrar(
                $papeletaLock, $usuario, 'RECHAZADA', $estadoAnteriorCodigo, EstadoPapeleta::RECHAZADO->value, $comentario
            );
        });

        $papeleta->refresh();
        $papeleta->trabajador->notify(new PapeletaRechazadaNotification($papeleta, $comentario));

        // Si rechazó RRHH, el jefe que ya había aprobado también debe enterarse.
        if ($rolActuando === RolUsuario::RRHH && $papeleta->jefe) {
            $papeleta->jefe->notify(new PapeletaRechazadaNotification($papeleta, $comentario));
        }

        return $papeleta;
    }
}
