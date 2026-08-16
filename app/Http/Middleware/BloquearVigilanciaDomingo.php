<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea por completo la garita (vista y TODAS sus acciones: resumen,
 * buscar, confirmar salida/retorno) los días no laborables — hoy en día
 * solo domingo, ver config('papeletas.dias_no_laborables'). Mismo criterio
 * que ya usa StorePapeletaRequest para no dejar crear papeletas esos días:
 * si no se trabaja, tampoco hay nada que marcar en la puerta.
 *
 * Las peticiones normales caen a la vista 'vigilancia.bloqueado' (un aviso,
 * no un error); las peticiones AJAX (buscar/resumen/confirmar) reciben un
 * JSON 423 Locked para que el front deje de disparar acciones.
 */
class BloquearVigilanciaDomingo
{
    public function handle(Request $request, Closure $next): Response
    {
        $diasNoLaborables = config('papeletas.dias_no_laborables', []);

        if (! in_array(Carbon::now()->dayOfWeek, $diasNoLaborables, true)) {
            return $next($request);
        }

        $mensaje = 'Hoy es domingo: la garita está deshabilitada. Buen domingo, descanse 😌';

        if ($request->wantsJson()) {
            return response()->json(['bloqueado' => true, 'mensaje' => $mensaje], 423);
        }

        return response()->view('vigilancia.bloqueado', ['mensaje' => $mensaje], 423);
    }
}
