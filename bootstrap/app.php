<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'bloquear.domingo.vigilancia' => \App\Http\Middleware\BloquearVigilanciaDomingo::class,
        ]);

        // Ya no se obliga a cambiar la contraseña: solo se sugiere con una
        // alerta flotante (ver layouts/partials/alerta-cambio-password).
        // El middleware ForzarCambioPassword se deja sin registrar para no
        // redirigir a la fuerza; la pantalla de cambio sigue disponible en
        // password.forzado.edit para quien acepte la sugerencia.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();