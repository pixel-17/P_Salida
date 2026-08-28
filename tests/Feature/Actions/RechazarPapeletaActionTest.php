<?php

namespace Tests\Feature\Actions;

use App\Actions\RechazarPapeletaAction;
use App\Enums\RolUsuario;
use App\Notifications\PapeletaRechazadaNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Tests\Support\PapeletaActionTestCase;

class RechazarPapeletaActionTest extends PapeletaActionTestCase
{
    public function test_el_jefe_rechaza_una_papeleta_solicitada(): void
    {
        Notification::fake();

        $papeleta = $this->crearPapeleta('SOLICITADO');

        (new RechazarPapeletaAction)->execute($papeleta, $this->jefe, 'No corresponde el destino');

        $papeleta->refresh();
        $this->assertSame('RECHAZADO', $papeleta->estado->codigo);

        $this->assertDatabaseHas('flujo_aprobaciones', [
            'papeleta_id' => $papeleta->id,
            'usuario_id' => $this->jefe->id,
            'rol' => RolUsuario::JEFE->value,
            'accion' => 'RECHAZADO',
        ]);

        // Solo se avisa al trabajador; el jefe no se notifica a sí mismo.
        Notification::assertSentTo($this->trabajador, PapeletaRechazadaNotification::class);
    }

    /**
     * Regresión: antes $usuario->rol no existía (los roles van por Spatie),
     * así que el rol actuante siempre quedaba mal registrado. Este test fija
     * que quede JEFE cuando rechaza el jefe y RRHH cuando rechaza RRHH.
     */
    public function test_rrhh_rechaza_y_se_registra_el_rol_correcto_no_jefe(): void
    {
        Notification::fake();

        $papeleta = $this->crearPapeleta('APROBADO_JEFE');

        (new RechazarPapeletaAction)->execute($papeleta, $this->rrhh, 'Falta documentación');

        $papeleta->refresh();
        $this->assertSame('RECHAZADO', $papeleta->estado->codigo);

        $this->assertDatabaseHas('flujo_aprobaciones', [
            'papeleta_id' => $papeleta->id,
            'usuario_id' => $this->rrhh->id,
            'rol' => RolUsuario::RRHH->value,
            'accion' => 'RECHAZADO',
        ]);
    }

    /**
     * Cuando rechaza RRHH, el jefe que ya había aprobado también debe
     * enterarse (además del trabajador).
     */
    public function test_si_rechaza_rrhh_se_avisa_tambien_al_jefe_que_ya_habia_aprobado(): void
    {
        Notification::fake();

        $papeleta = $this->crearPapeleta('APROBADO_JEFE');

        (new RechazarPapeletaAction)->execute($papeleta, $this->rrhh, 'Falta documentación');

        Notification::assertSentTo($this->trabajador, PapeletaRechazadaNotification::class);
        Notification::assertSentTo($this->jefe, PapeletaRechazadaNotification::class);
    }

    public function test_si_rechaza_el_jefe_no_se_notifica_a_si_mismo_como_jefe(): void
    {
        Notification::fake();

        $papeleta = $this->crearPapeleta('SOLICITADO');

        (new RechazarPapeletaAction)->execute($papeleta, $this->jefe, 'No corresponde');

        Notification::assertSentToTimes($this->jefe, PapeletaRechazadaNotification::class, 0);
    }

    public function test_un_trabajador_no_puede_rechazar_su_propia_papeleta(): void
    {
        $papeleta = $this->crearPapeleta('SOLICITADO');

        $this->expectException(AuthorizationException::class);

        (new RechazarPapeletaAction)->execute($papeleta, $this->trabajador, 'Intento inválido');
    }
}
