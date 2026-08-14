<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class SoloAdmin {
    public function handle(Request $request, Closure $next) {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Acceso restringido — Solo administradores.');
        }
        return $next($request);
    }
}
