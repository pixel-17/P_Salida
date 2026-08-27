<?php

namespace Tests\Feature\Commands;

use App\Models\NotificacionSistema;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PodarDatosAntiguosCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_borra_notificaciones_leidas_hace_mas_de_90_dias(): void
    {
        $vieja = $this->crearNotificacion(leidaHaceDias: 100);
        $reciente = $this->crearNotificacion(leidaHaceDias: 10);
        $noLeida = $this->crearNotificacion(leidaHaceDias: null);

        $this->artisan('papeletas:podar-datos-antiguos')->assertSuccessful();

        $this->assertDatabaseMissing('notificaciones_sistema', ['id' => $vieja->id]);
        $this->assertDatabaseHas('notificaciones_sistema', ['id' => $reciente->id]);
        $this->assertDatabaseHas('notificaciones_sistema', ['id' => $noLeida->id]);
    }

    public function test_respeta_el_umbral_de_dias_pasado_por_opcion(): void
    {
        $notificacion = $this->crearNotificacion(leidaHaceDias: 40);

        $this->artisan('papeletas:podar-datos-antiguos', ['--dias-notificaciones' => 30])
            ->assertSuccessful();

        $this->assertDatabaseMissing('notificaciones_sistema', ['id' => $notificacion->id]);
    }

    public function test_borra_suscripciones_push_inactivas_hace_mas_de_180_dias(): void
    {
        $vieja = $this->crearSuscripcion(activo: false, actualizadaHaceDias: 200);
        $inactivaReciente = $this->crearSuscripcion(activo: false, actualizadaHaceDias: 5);
        $activaVieja = $this->crearSuscripcion(activo: true, actualizadaHaceDias: 300);

        $this->artisan('papeletas:podar-datos-antiguos')->assertSuccessful();

        $this->assertDatabaseMissing('push_subscriptions', ['id' => $vieja->id]);
        $this->assertDatabaseHas('push_subscriptions', ['id' => $inactivaReciente->id]);
        $this->assertDatabaseHas('push_subscriptions', ['id' => $activaVieja->id]);
    }

    private function crearNotificacion(?int $leidaHaceDias): NotificacionSistema
    {
        $notificacion = NotificacionSistema::create([
            'user_id' => $this->user->id,
            'tipo' => 'PAPELETA_AUTORIZADA',
            'canal' => 'SISTEMA',
            'titulo' => 'Título de prueba',
            'mensaje' => 'Mensaje de prueba',
            'enviada_at' => now(),
        ]);

        if ($leidaHaceDias !== null) {
            $notificacion->forceFill(['leida_at' => now()->subDays($leidaHaceDias)])->save();
        }

        return $notificacion;
    }

    private function crearSuscripcion(bool $activo, int $actualizadaHaceDias): PushSubscription
    {
        $endpoint = 'https://push.example.com/'.bin2hex(random_bytes(8));

        $suscripcion = PushSubscription::create([
            'user_id' => $this->user->id,
            'endpoint' => $endpoint,
            'endpoint_hash' => hash('sha256', $endpoint),
            'p256dh' => 'clave-publica-de-prueba',
            'auth_token' => 'token-de-prueba',
            'activo' => $activo,
        ]);

        PushSubscription::whereKey($suscripcion->id)
            ->update(['updated_at' => now()->subDays($actualizadaHaceDias)]);

        return $suscripcion->fresh();
    }
}
