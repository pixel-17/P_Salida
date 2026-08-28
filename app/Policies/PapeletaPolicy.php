<?php

namespace App\Policies;

use App\Enums\EstadoPapeleta;
use App\Enums\RolUsuario;
use App\Enums\TipoObservacion;
use App\Models\Adjunto;
use App\Models\Papeleta;
use App\Models\User;

class PapeletaPolicy
{
    public function ver(User $user, Papeleta $papeleta): bool
    {
        return $user->id === $papeleta->trabajador_id
            || $user->id === $papeleta->jefe_id
            || $user->hasRole(RolUsuario::RRHH)
            || $user->hasRole(RolUsuario::ADMINISTRADOR);
    }

    public function crear(User $user): bool
    {
        return $user->hasRole(RolUsuario::TRABAJADOR) || $user->hasRole(RolUsuario::JEFE);
    }

    /**
     * Aprobar/rechazar/observar: quién puede actuar depende del estado actual.
     * SOLICITADO -> decide el jefe asignado.
     * APROBADO_JEFE -> decide RRHH.
     */
    public function decidir(User $user, Papeleta $papeleta): bool
    {
        return match ($papeleta->estado->codigo) {
            EstadoPapeleta::SOLICITADO->value => $user->id === $papeleta->jefe_id,
            EstadoPapeleta::APROBADO_JEFE->value => $user->hasRole(RolUsuario::RRHH),
            default => false,
        };
    }

    /**
     * Regla vigente: el trabajador NUNCA marca su propia salida/retorno, en
     * ninguna sede. Ese privilegio es exclusivo del vigilante (ver
     * marcarComoVigilante). Se deja el método (siempre false) en vez de
     * borrarlo para que cualquier código viejo que lo consulte falle cerrado.
     */
    public function marcar(User $user, Papeleta $papeleta): bool
    {
        return false;
    }

    /**
     * El trabajador puede ver el código/QR de su propia papeleta para
     * mostrárselo al vigilante en la puerta. No marca nada por sí mismo.
     *
     * La fecha autorizada no es negociable (mismo criterio que el
     * vigilante — ver MarcarSalidaVigilanteAction y
     * VigilanteController::buscar/puede_salida): si todavía falta para la
     * fecha_salida, el código NO se muestra, aunque la papeleta ya esté
     * APROBADO_RRHH. Mostrarlo antes de tiempo no sirve para nada (el
     * vigilante lo va a rechazar igual) y confunde al trabajador.
     *
     * El retorno (EN_CURSO) es la excepción: no se ata a fecha_salida
     * porque puede cruzar medianoche, igual que puede_retorno en el
     * vigilante, que tampoco valida fecha para el retorno.
     */
    public function verCodigo(User $user, Papeleta $papeleta): bool
    {
        if ($user->id !== $papeleta->trabajador_id) {
            return false;
        }

        return match ($papeleta->estado->codigo) {
            EstadoPapeleta::APROBADO_RRHH->value => $papeleta->esHoyFechaDeSalida(),
            EstadoPapeleta::EN_CURSO->value => true,
            default => false,
        };
    }

    /**
     * El vigilante confirma salida/retorno en la puerta, ya sea escaneando
     * el QR de la papeleta o ingresando el código a mano. Aplica en TODAS
     * las sedes (ya no depende de un flag por sede) y solo para el
     * vigilante de la MISMA sede que la papeleta (no cualquier vigilante de
     * cualquier sede).
     */
    public function marcarComoVigilante(User $user, Papeleta $papeleta): bool
    {
        return $user->esVigilante()
            && $user->sede_id === $papeleta->sede_id
            && in_array($papeleta->estado->codigo, [
                EstadoPapeleta::APROBADO_RRHH->value,
                EstadoPapeleta::EN_CURSO->value,
            ], true);
    }

    /**
     * Adjuntar documento: solo el propio trabajador. Cada subida reemplaza
     * cualquier adjunto anterior (ver AdjuntoController::store) — nunca hay
     * más de uno vigente por papeleta. Dos casos habilitan la subida:
     *  - El motivo de la papeleta exige documento y todavía nadie decidió
     *    (SOLICITADO): se puede subir o volver a subir para corregirlo.
     *    Pasado ese punto (jefe/RRHH ya actuaron) ya no se toca sin pasar
     *    por una observación.
     *  - Le observaron pidiendo sustento (JUSTIFICACION) y esa observación
     *    sigue sin atender — aquí SÍ se permite reemplazar aunque ya tenga
     *    uno, porque es justo lo que la observación está pidiendo.
     */
    public function adjuntar(User $user, Papeleta $papeleta): bool
    {
        if ($user->id !== $papeleta->trabajador_id) {
            return false;
        }

        $pideSustentoPorObservacion = $papeleta->observaciones
            ->where('atendida', false)
            ->contains(fn ($o) => $o->tipo === TipoObservacion::JUSTIFICACION);

        if ($pideSustentoPorObservacion) {
            return true;
        }

        // Ya presentó sustento (ver Papeleta::estadoSustento — "presentado")
        // y nadie lo está pidiendo de nuevo vía observación: no hay motivo
        // para dejarlo subir otro archivo.
        if ($papeleta->adjuntos->isNotEmpty()) {
            return false;
        }

        return $papeleta->motivo->requiere_documento
            && $papeleta->estado->codigo === EstadoPapeleta::SOLICITADO->value;
    }

    /**
     * Eliminar un adjunto: solo el propio trabajador que lo subió, y solo
     * mientras la papeleta sigue en revisión (SOLICITADO/OBSERVADO). Una vez
     * aprobada por RRHH o en curso/finalizada, el adjunto queda como
     * evidencia y ya no se puede tocar. El administrador es la única
     * excepción a la restricción de estado.
     *
     * EXCEPCIÓN ADICIONAL, sin importar el estado ni el rol (ni siquiera el
     * administrador): un adjunto subido como respuesta a una observación
     * (tiene observacion_id, ver Adjunto::observacion) es evidencia del
     * expediente y jamás se puede eliminar, aunque la papeleta vuelva a
     * SOLICITADO/OBSERVADO más adelante por otro motivo.
     */
    public function eliminarAdjunto(User $user, Papeleta $papeleta, Adjunto $adjunto): bool
    {
        if ($adjunto->observacion_id !== null) {
            return false;
        }

        if ($user->hasRole(RolUsuario::ADMINISTRADOR)) {
            return true;
        }

        return $user->id === $papeleta->trabajador_id
            && in_array($papeleta->estado->codigo, [
                EstadoPapeleta::SOLICITADO->value,
                EstadoPapeleta::OBSERVADO->value,
            ], true);
    }

    /**
     * Responder una observación: solo el propio trabajador, y solo mientras
     * la papeleta sigue OBSERVADO (ver ResponderObservacionAction — al
     * responder, la papeleta vuelve a quien la observó para que decida).
     */
    public function responderObservacion(User $user, Papeleta $papeleta): bool
    {
        return $user->id === $papeleta->trabajador_id
            && $papeleta->estado->codigo === EstadoPapeleta::OBSERVADO->value;
    }

    /**
     * Cancelación manual del trabajador: únicamente el dueño de la
     * papeleta puede cancelarla — ni siquiera el administrador tiene
     * excepción aquí. Y solo mientras todavía no marcó salida con el
     * vigilante (SOLICITADO/APROBADO_JEFE/APROBADO_RRHH/OBSERVADO): una
     * vez que pasa a EN_CURSO ya no se puede "cancelar" — ahí lo que
     * corresponde es que vuelva y se cierre normal, o que quede
     * VENCIDA/Sin retorno si no vuelve.
     */
    public function cancelar(User $user, Papeleta $papeleta): bool
    {
        return $user->id === $papeleta->trabajador_id
            && in_array($papeleta->estado->codigo, [
                EstadoPapeleta::SOLICITADO->value,
                EstadoPapeleta::APROBADO_JEFE->value,
                EstadoPapeleta::APROBADO_RRHH->value,
                EstadoPapeleta::OBSERVADO->value,
            ], true);
    }
}
