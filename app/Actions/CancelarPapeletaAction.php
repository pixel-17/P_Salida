<?php

namespace App\Actions;

use App\Enums\EstadoPapeleta;
use App\Models\Estado;
use App\Models\HistorialPapeleta;
use App\Models\Papeleta;
use App\Models\User;
use App\Notifications\PapeletaCanceladaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Cancelación manual: el propio trabajador cancela su papeleta antes de
 * salir (ver PapeletaPolicy::cancelar para las etapas permitidas). El
 * motivo escrito por el trabajador queda en el historial y se le avisa
 * al jefe. A diferencia de la cancelación automática de fin de día (que
 * ahora cae en VENCIDA, ver CancelarPapeletasNoPresentadasCommand), esta
 * SIEMPRE es una decisión explícita del trabajador.
 */
class CancelarPapeletaAction
{
    public function execute(Papeleta $papeleta, User $usuario, string $motivo): Papeleta
    {
        Gate::forUser($usuario)->authorize('cancelar', $papeleta);

        DB::transaction(function () use ($papeleta, $usuario, $motivo) {
            // Lock + re-chequeo de autorización contra el estado real: evita
            // cancelar una papeleta que, entre el check inicial y este punto,
            // ya cambió de estado (ej. el vigilante ya marcó salida, o el
            // barrido de fin de día ya la cerró). Mismo patrón que
            // AprobarPapeletaAction.
            $papeletaLock = Papeleta::whereKey($papeleta->id)->lockForUpdate()->first();

            Gate::forUser($usuario)->authorize('cancelar', $papeletaLock);

            $estadoAnteriorCodigo = $papeletaLock->estado->codigo;

            $papeletaLock->update(['estado_id' => Estado::porCodigo(EstadoPapeleta::CANCELADO)->id]);

            HistorialPapeleta::registrar(
                $papeletaLock,
                $usuario,
                'CANCELADA_POR_TRABAJADOR',
                $estadoAnteriorCodigo,
                EstadoPapeleta::CANCELADO->value,
                $motivo
            );
        });

        $papeleta->refresh();

        $papeleta->jefe?->notify(new PapeletaCanceladaNotification($papeleta, $motivo));

        return $papeleta;
    }
}
