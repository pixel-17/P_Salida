<?php

namespace App\Policies;

use App\Models\User;

/**
 * Cubre "Mi equipo": el jefe crea trabajadores para sí mismo y ve solo los
 * suyos (no puede editar ni eliminar); el administrador entra a la misma
 * pantalla con CRUD completo sobre cualquier trabajador. No confundir con
 * el CRUD general de /users (RRHH/Jefe/Vigilante/Administrador), que sigue
 * siendo exclusivo de ADMINISTRADOR vía middleware — esto es solo para la
 * relación jefe → trabajador.
 */
class UserPolicy
{
    public function verEquipo(User $user): bool
    {
        return $user->esJefe() || $user->esAdmin();
    }

    public function crearTrabajador(User $user): bool
    {
        return $user->esJefe() || $user->esAdmin();
    }

    /**
     * El jefe solo ve el detalle de trabajadores que son suyos; el admin ve
     * cualquiera.
     */
    public function verTrabajador(User $user, User $trabajador): bool
    {
        return $user->esAdmin() || $user->id === $trabajador->jefe_id;
    }

    /**
     * Editar/eliminar trabajadores es exclusivo del administrador. El jefe
     * pidió expresamente "solo crear, no eliminar ni editar" — se deja
     * así de explícito para que quede claro que no es un olvido.
     */
    public function editarTrabajador(User $user, User $trabajador): bool
    {
        return $user->esAdmin();
    }

    public function eliminarTrabajador(User $user, User $trabajador): bool
    {
        return $user->esAdmin();
    }
}
