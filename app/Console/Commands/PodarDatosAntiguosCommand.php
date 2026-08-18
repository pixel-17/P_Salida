<?php

namespace App\Console\Commands;

use App\Models\NotificacionSistema;
use App\Models\PushSubscription;
use Illuminate\Console\Command;

/**
 * Poda datos que solo crecen y que no tienen valor de auditoría a largo
 * plazo. NO toca historial_papeletas, audit_logs ni marcaciones: esas son
 * el registro real del trámite (quién aprobó qué y cuándo salió/volvió
 * alguien) y deben conservarse indefinidamente.
 *
 * Lo que sí se poda:
 * - notificaciones_sistema ya leídas hace tiempo: una vez leída, lo que
 *   importa del evento (quién hizo qué y cuándo) ya vive en
 *   historial_papeletas; el texto de la notificación en sí es solo para
 *   la campana/toast del momento.
 * - push_subscriptions inactivas hace tiempo (ver EnviarWebPushJob: se
 *   desactivan con activo=false cuando el navegador responde 404/410,
 *   pero nunca se borraban).
 */
class PodarDatosAntiguosCommand extends Command
{
    protected $signature = 'papeletas:podar-datos-antiguos
        {--dias-notificaciones=90 : Notificaciones leídas hace más de N días}
        {--dias-suscripciones=180 : Suscripciones push inactivas hace más de N días}';

    protected $description = 'Elimina notificaciones leídas y suscripciones push inactivas ya viejas, para no sobrecargar la base de datos';

    public function handle(): int
    {
        $diasNotificaciones = (int) $this->option('dias-notificaciones');
        $diasSuscripciones = (int) $this->option('dias-suscripciones');

        $notificacionesBorradas = NotificacionSistema::query()
            ->whereNotNull('leida_at')
            ->where('leida_at', '<', now()->subDays($diasNotificaciones))
            ->delete();

        $suscripcionesBorradas = PushSubscription::query()
            ->where('activo', false)
            ->where('updated_at', '<', now()->subDays($diasSuscripciones))
            ->delete();

        $this->info("Notificaciones leídas eliminadas (>{$diasNotificaciones} días): {$notificacionesBorradas}");
        $this->info("Suscripciones push inactivas eliminadas (>{$diasSuscripciones} días): {$suscripcionesBorradas}");

        return self::SUCCESS;
    }
}
