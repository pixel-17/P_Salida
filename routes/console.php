<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * El scheduler de Laravel NO reintenta ni avisa solo cuando un comando
 * revienta: si no se engancha onFailure(), el fallo queda enterrado en
 * storage/logs y nadie se entera hasta que el efecto (papeletas que no
 * vencieron, alertas que no salieron) se nota manualmente — que es
 * justamente como se detectó el bug original de este archivo. Este helper
 * asegura que TODO Schedule::command() de abajo loguee a nivel 'critical'
 * si falla, para que llegue al canal 'slack' (ya configurado en
 * config/logging.php) en cuanto LOG_STACK lo incluya en el .env de
 * producción.
 */
if (! function_exists('conAlertaSiFalla')) {
    function conAlertaSiFalla(\Illuminate\Console\Scheduling\Event $evento, string $comando): \Illuminate\Console\Scheduling\Event
    {
        return $evento->onFailure(function () use ($comando) {
            Log::critical("Comando programado falló: {$comando}", [
                'comando' => $comando,
                'hora' => now()->toDateTimeString(),
            ]);
        });
    }
}

// Sin esto, EN_CURSO nunca pasa a VENCIDA automáticamente: el comando
// existía pero no estaba programado en ningún lado.
conAlertaSiFalla(
    Schedule::command('papeletas:marcar-vencidas')
        ->everyFiveMinutes()
        ->withoutOverlapping(),
    'papeletas:marcar-vencidas'
);

// Alerta temprana (no cierra nada): 30 min tarde sin marcar salida.
conAlertaSiFalla(
    Schedule::command('papeletas:alertar-salida-no-marcada')
        ->everyFiveMinutes()
        ->withoutOverlapping(),
    'papeletas:alertar-salida-no-marcada'
);

// Cierre definitivo de fin de día para quien nunca se presentó a marcar
// salida. Corre cerca de medianoche, ya con el día prácticamente cerrado.
conAlertaSiFalla(
    Schedule::command('papeletas:cancelar-no-presentadas')
        ->dailyAt('23:55')
        ->withoutOverlapping(),
    'papeletas:cancelar-no-presentadas'
);

// Mantenimiento de tablas que solo crecen (notificaciones ya leídas,
// suscripciones push muertas). No toca historial_papeletas ni audit_logs:
// esas son el registro de auditoría real y se conservan indefinidamente.
// Semanal y de madrugada porque no es una operación urgente.
conAlertaSiFalla(
    Schedule::command('papeletas:podar-datos-antiguos')
        ->weeklyOn(1, '03:00')
        ->withoutOverlapping(),
    'papeletas:podar-datos-antiguos'
);
