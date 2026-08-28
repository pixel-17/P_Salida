<?php

namespace Tests\Feature\Actions;

use Tests\Support\PapeletaActionTestCase;

use App\Actions\CancelarPapeletaAction;
use App\Models\User;
use App\Notifications\PapeletaCanceladaNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;

class CancelarPapeletaActionTest extends PapeletaActionTestCase
{
    public function test_el_trabajador_cancela_su_propia_papeleta_solicitada(): void
    {
        Notification::fake();

        $papeleta = $this->crearPapeleta('SOLICITADO');

        (new CancelarPapeletaAction)->execute($papeleta, $this->trabajador, 'Ya no lo necesito');

        $papeleta->refresh();
        $this->assertSame('CANCELADO', $papeleta->estado->codigo);

        Notification::assertSentTo($this->jefe, PapeletaCanceladaNotification::class);
    }

    /**
     * Cubre las 4 etapas "en trámite" en las que sí se puede cancelar
     * manualmente, antes de que el vigilante marque la salida.
     */
    public function test_se_puede_cancelar_en_cualquier_etapa_previa_a_marcar_salida(): void
    {
        foreach (['SOLICITADO', 'APROBADO_JEFE', 'APROBADO_RRHH', 'OBSERVADO'] as $estadoCodigo) {
            $papeleta = $this->crearPapeleta($estadoCodigo);

            (new CancelarPapeletaAction)->execute($papeleta, $this->trabajador, 'Motivo suficientemente largo');

            $papeleta->refresh();
            $this->assertSame(
                'CANCELADO',
                $papeleta->estado->codigo,
                "Debe poder cancelarse estando en {$estadoCodigo}."
            );
        }
    }

    /**
     * Una vez que el vigilante marcó salida (EN_CURSO), ya no es
     * "cancelable": el cierre a partir de ahí lo maneja el vencimiento
     * automático (ver MarcarPapeletasVencidasCommand), no la cancelación
     * manual del trabajador.
     */
    public function test_no_se_puede_cancelar_una_papeleta_en_curso(): void
    {
        $papeleta = $this->crearPapeleta('EN_CURSO');
        $this->marcarSalida($papeleta);

        $this->expectException(AuthorizationException::class);

        (new CancelarPapeletaAction)->execute($papeleta, $this->trabajador, 'Intento tardío');
    }

    public function test_ni_siquiera_el_administrador_puede_cancelar_la_papeleta_de_otro(): void
    {
        $papeleta = $this->crearPapeleta('SOLICITADO');

        $admin = User::factory()->create(['sede_id' => $this->sede->id]);
        $admin->assignRole('ADMINISTRADOR');

        $this->expectException(AuthorizationException::class);

        (new CancelarPapeletaAction)->execute($papeleta, $admin, 'Intento de admin');
    }

    public function test_otro_trabajador_no_puede_cancelar_una_papeleta_ajena(): void
    {
        $papeleta = $this->crearPapeleta('SOLICITADO');

        $otro = User::factory()->create(['sede_id' => $this->sede->id]);
        $otro->assignRole('TRABAJADOR');

        $this->expectException(AuthorizationException::class);

        (new CancelarPapeletaAction)->execute($papeleta, $otro, 'No es mía');
    }
}
