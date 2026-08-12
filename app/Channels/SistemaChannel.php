<?php

namespace App\Channels;

use App\Enums\CanalNotificacion;
use App\Models\NotificacionSistema;
use App\Notifications\BasePapeletaNotification;
use Illuminate\Notifications\Notification;

class SistemaChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof BasePapeletaNotification) {
            return;
        }

        NotificacionSistema::create([
            'user_id' => $notifiable->id,
            'papeleta_id' => $notification->papeleta->id,
            'tipo' => $notification->tipo(),
            'canal' => CanalNotificacion::SISTEMA->value,
            'titulo' => $notification->titulo(),
            'mensaje' => $notification->mensaje(),
            'enviada_at' => now(),
        ]);
    }
}
