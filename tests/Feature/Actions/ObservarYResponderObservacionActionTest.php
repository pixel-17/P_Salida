<?php

namespace Tests\Feature\Actions;

use App\Actions\ObservarPapeletaAction;
use App\Actions\ResponderObservacionAction;
use App\Enums\TipoObservacion;
use Illuminate\Validation\ValidationException;
use Tests\Support\PapeletaActionTestCase;

class ObservarYResponderObservacionActionTest extends PapeletaActionTestCase
{
    public function test_el_jefe_observa_una_papeleta_solicitada(): void
    {
        $papeleta = $this->crearPapeleta('SOLICITADO');

        (new ObservarPapeletaAction)->execute(
            $papeleta, $this->jefe, 'Falta destino claro', TipoObservacion::ADMINISTRATIVA
        );

        $papeleta->refresh();
        $this->assertSame('OBSERVADO', $papeleta->estado->codigo);
        $this->assertDatabaseHas('observaciones', [
            'papeleta_id' => $papeleta->id,
            'usuario_id' => $this->jefe->id,
            'tipo' => TipoObservacion::ADMINISTRATIVA->value,
        ]);
    }

    /**
     * Regresión: antes, cualquier observación de tipo ADMINISTRATIVA mandaba
     * la papeleta directo a APROBADO_JEFE (para que la viera RRHH) sin
     * importar si quien observó fue el Jefe con la papeleta todavía en
     * SOLICITADO — saltándose la aprobación del Jefe. Ahora debe volver
     * exactamente al estado desde el que se observó.
     */
    public function test_al_responder_una_observacion_del_jefe_vuelve_a_solicitado_sin_importar_el_tipo(): void
    {
        $papeleta = $this->crearPapeleta('SOLICITADO');

        (new ObservarPapeletaAction)->execute(
            $papeleta, $this->jefe, 'Falta un dato', TipoObservacion::ADMINISTRATIVA
        );
        $papeleta->refresh();

        (new ResponderObservacionAction)->execute($papeleta, $this->trabajador, 'Ya lo corregí');
        $papeleta->refresh();

        $this->assertSame(
            'SOLICITADO',
            $papeleta->estado->codigo,
            'Debe volver a SOLICITADO (a que decida el Jefe), no saltar directo a RRHH.'
        );
    }

    public function test_al_responder_una_observacion_de_rrhh_vuelve_a_aprobado_jefe(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_JEFE');

        (new ObservarPapeletaAction)->execute(
            $papeleta, $this->rrhh, 'Falta sustento', TipoObservacion::JUSTIFICACION
        );
        $papeleta->refresh();

        (new ResponderObservacionAction)->execute($papeleta, $this->trabajador, 'Adjunto sustento');
        $papeleta->refresh();

        $this->assertSame('APROBADO_JEFE', $papeleta->estado->codigo);
    }

    public function test_no_se_puede_responder_si_no_hay_observacion_pendiente(): void
    {
        // Estado OBSERVADO a propósito (para pasar la Policy, que ya exige
        // ese estado) pero sin ninguna fila en `observaciones`: cubre el
        // caso de inconsistencia de datos, no el de "estado equivocado"
        // (ese lo cubre la Policy antes, con AuthorizationException).
        $papeleta = $this->crearPapeleta('OBSERVADO');

        $this->expectException(ValidationException::class);

        (new ResponderObservacionAction)->execute($papeleta, $this->trabajador, 'Nada que responder');
    }
}
