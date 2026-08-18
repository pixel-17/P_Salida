<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sin esto, EN_CURSO nunca pasa a VENCIDA automáticamente: el comando
// existía pero no estaba programado en ningún lado.
Schedule::command('papeletas:marcar-vencidas')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Alerta temprana (no cierra nada): 30 min tarde sin marcar salida.
Schedule::command('papeletas:alertar-salida-no-marcada')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Cierre definitivo de fin de día para quien nunca se presentó a marcar
// salida. Corre cerca de medianoche, ya con el día prácticamente cerrado.
Schedule::command('papeletas:cancelar-no-presentadas')
    ->dailyAt('23:55')
    ->withoutOverlapping();

// Mantenimiento de tablas que solo crecen (notificaciones ya leídas,
// suscripciones push muertas). No toca historial_papeletas ni audit_logs:
// esas son el registro de auditoría real y se conservan indefinidamente.
// Semanal y de madrugada porque no es una operación urgente.
Schedule::command('papeletas:podar-datos-antiguos')
    ->weeklyOn(1, '03:00')
    ->withoutOverlapping();
