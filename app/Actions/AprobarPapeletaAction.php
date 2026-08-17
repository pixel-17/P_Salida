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
use App\Notifications\PapeletaAprobadaJefeNotification;
use App\Notifications\PapeletaAprobadaRrhhNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class AprobarPapeletaAction
{
    /**
     * @throws AuthorizationException
     */
    public function execute(Papeleta $papeleta, User $aprobador, ?string $comentario = null): Papeleta
    {
        // Chequeo temprano (sin lock) para no ni siquiera abrir transacción si
        // el estado visible ya no permite decidir; el chequeo que de verdad
        // importa es el de adentro, contra la fila bloqueada.
        Gate::forUser($aprobador)->authorize('decidir', $papeleta);

        $esJefeDecidiendo = DB::transaction(function () use ($papeleta, $aprobador, $comentario) {
            // SELECT ... FOR UPDATE: si dos aprobadores (o el mismo con doble
            // clic) llegan casi al mismo tiempo, el segundo espera a que el
            // primero termine la transacción y luego relee el estado ya
            // actualizado, así que su propio authorize('decidir', ...) falla
            // limpio en vez de generar un segundo FlujoAprobacion/notificación
            // duplicados sobre una papeleta que ya cambió de estado.
            $papeletaLock = Papeleta::whereKey($papeleta->id)->lockForUpdate()->first();

            Gate::forUser($aprobador)->authorize('decidir', $papeletaLock);

            $estadoAnteriorCodigo = $papeletaLock->estado->codigo;
            $esJefeDecidiendo = $estadoAnteriorCodigo === EstadoPapeleta::SOLICITADO->value;
            $nuevoCodigo = $esJefeDecidiendo ? EstadoPapeleta::APROBADO_JEFE : EstadoPapeleta::APROBADO_RRHH;

            $papeletaLock->update(['estado_id' => Estado::porCodigo($nuevoCodigo)->id]);

            FlujoAprobacion::create([
                'papeleta_id' => $papeletaLock->id,
                'usuario_id' => $aprobador->id,
                'rol' => $esJefeDecidiendo ? RolUsuario::JEFE->value : RolUsuario::RRHH->value,
                'accion' => AccionFlujo::APROBADO->value,
                'comentario' => $comentario,
            ]);

            HistorialPapeleta::registrar(
                $papeletaLock, $aprobador, 'APROBADA', $estadoAnteriorCodigo, $nuevoCodigo->value, $comentario
            );

            return $esJefeDecidiendo;
        });

        $papeleta->refresh();

        if ($esJefeDecidiendo) {
            // Jefe aprueba: notifica solo a RRHH (el trabajador todavía no
            // debe ser notificado, falta la aprobación de RRHH).
            // El proyecto usa Spatie Permission (roles vía tabla pivote),
            // no una columna "rol" en users — ->where('rol', ...) no existe
            // y tronaba. Se usa el scope role() que trae Spatie.
            Notification::send(User::role(RolUsuario::RRHH->value)->get(), new PapeletaAprobadaJefeNotification($papeleta));
        } else {
            // RRHH aprueba: notifica solo al trabajador.
            $papeleta->trabajador->notify(new PapeletaAprobadaRrhhNotification($papeleta));
        }

        return $papeleta;
    }
}
