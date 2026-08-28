<?php

use App\Http\Middleware\BloquearVigilanciaDomingo;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            'bloquear.domingo.vigilancia' => BloquearVigilanciaDomingo::class,
        ]);

        // Ya no se obliga a cambiar la contraseña: solo se sugiere con una
        // alerta flotante (ver layouts/partials/alerta-cambio-password), sin
        // redirigir a la fuerza. El middleware que hacía esa redirección
        // (ForzarCambioPassword) se eliminó por no usarse; la pantalla de
        // cambio sigue disponible en password.forzado.edit para quien
        // acepte la sugerencia (ver ForzarCambioPasswordController).
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Antes de esto, un 500 en producción solo quedaba en
        // storage/logs/laravel.log — nadie lo veía hasta que un usuario se
        // quejaba. No agrega un servicio nuevo (Sentry/Flare, etc.): usa el
        // canal 'slack' que ya existe en config/logging.php, así que solo
        // requiere que el .env de producción tenga LOG_SLACK_WEBHOOK_URL y
        // que LOG_STACK incluya "slack" (p. ej. LOG_STACK=single,slack).
        // Se excluyen expresamente las excepciones de validación/auth/404,
        // que son tráfico normal, no incidentes.
        $exceptions->report(function (Throwable $e) {
            // Sin webhook configurado (local/testing), no intentamos loguear
            // a Slack: el handler de Monolog revienta con una URL vacía, y
            // el reporte normal a storage/logs ya ocurre solo (comportamiento
            // por defecto de Laravel, este closure no lo reemplaza).
            if (! config('logging.channels.slack.url')) {
                return;
            }

            if ($e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || $e instanceof NotFoundHttpException
                || $e instanceof ModelNotFoundException) {
                return;
            }

            Log::channel('slack')->critical($e->getMessage(), [
                'excepcion' => get_class($e),
                'archivo' => $e->getFile().':'.$e->getLine(),
            ]);
        });
    })->create();
