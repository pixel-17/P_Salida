<?php

namespace App\Notifications;

use App\Models\Papeleta;
use Illuminate\Notifications\Notification;

/**
 * Cada notificación concreta define tipo()/titulo()/mensaje().
 * via() manda por dos canales propios (no los nativos de Laravel):
 * - "sistema": guarda fila en notificaciones_sistema (canal=SISTEMA) para la campana.
 * - "webpush": envía Web Push real (VAPID) y además deja constancia en
 *   notificaciones_sistema (canal=PUSH) con su propio enviada_at.
 *
 * IMPORTANTE: esta clase ya NO implementa ShouldQueue. Antes lo hacía, y con
 * QUEUE_CONNECTION=database eso significaba que el insert en
 * notificaciones_sistema (lo que alimenta la campana) quedaba encolado en la
 * tabla `jobs` esperando a que alguien corra `queue:work` — sin worker
 * corriendo, la notificación nunca llegaba, ni con polling ni refrescando.
 *
 * Ahora notify() corre en línea: el insert de "sistema" es instantáneo
 * (una fila, sin I/O externo). Lo único que sí vale la pena encolar es el
 * envío real de Web Push (llamada HTTP a servicios externos, puede demorar
 * o fallar) — eso lo encola WebPushChannel puntualmente con su propio Job,
 * no toda la notificación. Ver App\Jobs\EnviarWebPushJob.
 */
abstract class BasePapeletaNotification extends Notification
{
    public function __construct(public Papeleta $papeleta)
    {
    }

    abstract public function tipo(): string;

    abstract public function titulo(): string;

    abstract public function mensaje(): string;

    public function via(object $notifiable): array
    {
        return ['sistema', 'webpush'];
    }
}
