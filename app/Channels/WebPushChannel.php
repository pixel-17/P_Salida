<?php

namespace App\Channels;

use App\Jobs\EnviarWebPushJob;
use App\Notifications\BasePapeletaNotification;
use Illuminate\Notifications\Notification;

class WebPushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof BasePapeletaNotification) {
            return;
        }

        // El trabajo real (llamada HTTP a servicios push + el insert de
        // constancia en notificaciones_sistema) va en un Job encolado
        // aparte: esto es lo único del flujo de notificaciones que sí debe
        // esperar a un queue:worker, porque es I/O externo lento/flaky.
        EnviarWebPushJob::dispatch(
            $notifiable,
            $notification->papeleta->id,
            $notification->tipo(),
            $notification->titulo(),
            $notification->mensaje(),
        );
    }
}
