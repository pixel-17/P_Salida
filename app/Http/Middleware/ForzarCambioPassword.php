<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el usuario autenticado tiene must_change_password = true (contraseña
 * inicial = DNI, asignada por su Jefe/Administrador), lo redirige a la
 * pantalla obligatoria de cambio antes de dejarlo pasar a cualquier otra
 * ruta. Se excluyen esa misma pantalla y logout para no crear un loop.
 */
class ForzarCambioPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->must_change_password
            && ! $request->routeIs('password.forzado.*')
            && ! $request->routeIs('logout')
        ) {
            return redirect()->route('password.forzado.edit');
        }

        return $next($request);
    }
}
