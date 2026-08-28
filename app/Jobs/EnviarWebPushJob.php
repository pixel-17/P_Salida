<?php

namespace App\Jobs;

use App\Enums\CanalNotificacion;
use App\Models\NotificacionSistema;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Único paso de todo el flujo de notificaciones que sigue en cola: es una
 * llamada HTTP a servicios externos (FCM/Mozilla push), puede demorar o
 * fallar, no debe bloquear la request que la disparó (crear/aprobar/marcar
 * papeleta). El insert en notificaciones_sistema para la campana NO pasa por
 * acá — ese es síncrono, ver BasePapeletaNotification.
 *
 * Requiere `php artisan queue:work` corriendo (ver Supervisor). Si no hay
 * worker activo, el push al celular no llega — pero la campana/toast en la
 * web sí, porque esos ya no dependen de la cola.
 */
class EnviarWebPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public User $notifiable,
        public int $papeletaId,
        public string $tipo,
        public string $titulo,
        public string $mensaje,
    ) {}

    public function handle(): void
    {
        $subscripciones = $this->notifiable->pushSubscriptions()->activas()->get();

        if ($subscripciones->isEmpty()) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ]);

        $payload = json_encode([
            'title' => $this->titulo,
            'body' => $this->mensaje,
            'url' => url("/papeletas/{$this->papeletaId}"),
        ]);

        foreach ($subscripciones as $sub) {
            $webPush->queueNotification(
                Subscription::create($sub->toWebPushSubscription()),
                $payload
            );
        }

        $enviadoAlMenosUno = false;

        foreach ($webPush->flush() as $reporte) {
            $endpoint = $reporte->getRequest()->getUri()->__toString();
            $subscripcion = $subscripciones->firstWhere('endpoint', $endpoint);

            if ($reporte->isSuccess()) {
                $enviadoAlMenosUno = true;

                continue;
            }

            // 404/410 = suscripción expirada o revocada por el navegador: desactivar sin borrar.
            if ($subscripcion && in_array($reporte->getResponse()?->getStatusCode(), [404, 410], true)) {
                $subscripcion->update(['activo' => false]);
            }

            Log::warning('Web Push fallido', [
                'endpoint' => $endpoint,
                'reason' => $reporte->getReason(),
            ]);
        }

        NotificacionSistema::create([
            'user_id' => $this->notifiable->id,
            'papeleta_id' => $this->papeletaId,
            'tipo' => $this->tipo,
            'canal' => CanalNotificacion::PUSH->value,
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'enviada_at' => $enviadoAlMenosUno ? now() : null,
        ]);
    }
}
