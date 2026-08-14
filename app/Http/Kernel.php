<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * Middleware global (se ejecuta en TODAS las peticiones)
     */
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * Grupos de middleware
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,

            // Para autenticación de sesión
            \Illuminate\Session\Middleware\AuthenticateSession::class,

            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,

            // Binding de rutas (MUY IMPORTANTE)
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * Middleware individuales (alias)
     */
    protected $middlewareAliases = [
        // 🔐 Autenticación
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,

        // 🔒 Sesión
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,

        // 🚫 Invitados
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,

        // 🔑 Contraseña confirmada
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,

        // 🔗 Firmas
        'signed' => \App\Http\Middleware\ValidateSignature::class,

        // 🚦 Rate limit
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        // 📧 Email verificado
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        // 🔥 TU MIDDLEWARE PERSONALIZADO
        'solo.admin' => \App\Http\Middleware\SoloAdmin::class,
    ];
}