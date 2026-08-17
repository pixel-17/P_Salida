<?php

namespace Tests\Feature\Actions;

use App\Actions\MarcarRetornoVigilanteAction;
use App\Enums\TipoMarcacion;
use Illuminate\Validation\ValidationException;

class MarcarRetornoVigilanteActionTest extends PapeletaActionTestCase
{
    public function test_el_vigilante_marca_retorno_y_la_papeleta_finaliza(): void
    {
        $papeleta = $this->crearPapeleta('EN_CURSO');
        $this->marcarSalida($papeleta);

        (new MarcarRetornoVigilanteAction)->execute($papeleta, $this->vigilante);

        $papeleta->refresh();
        $this->assertSame('FINALIZADO', $papeleta->estado->codigo);
        $this->assertDatabaseHas('marcaciones', [
            'papeleta_id' => $papeleta->id,
            'tipo' => TipoMarcacion::RETORNO->value,
        ]);
    }

    public function test_no_se_puede_marcar_retorno_sin_salida_previa(): void
    {
        $papeleta = $this->crearPapeleta('EN_CURSO');

        $this->expectException(ValidationException::class);

        (new MarcarRetornoVigilanteAction)->execute($papeleta, $this->vigilante);
    }

    public function test_no_se_puede_marcar_retorno_dos_veces(): void
    {
        $papeleta = $this->crearPapeleta('EN_CURSO');
        $this->marcarSalida($papeleta);

        (new MarcarRetornoVigilanteAction)->execute($papeleta, $this->vigilante);
        $papeleta->refresh();

        $this->expectException(ValidationException::class);

        (new MarcarRetornoVigilanteAction)->execute($papeleta, $this->vigilante);
    }

    /**
     * Mismo caso de carrera que en MarcarSalidaVigilanteActionTest, ahora
     * para el retorno: dos instancias independientes que leyeron la
     * papeleta antes de que cualquiera marcara retorno.
     */
    public function test_doble_marcacion_de_retorno_simultanea_no_duplica(): void
    {
        $papeleta = $this->crearPapeleta('EN_CURSO');
        $this->marcarSalida($papeleta);

        $instanciaA = \App\Models\Papeleta::find($papeleta->id);
        $instanciaB = \App\Models\Papeleta::find($papeleta->id);

        (new MarcarRetornoVigilanteAction)->execute($instanciaA, $this->vigilante);

        $this->expectException(ValidationException::class);

        (new MarcarRetornoVigilanteAction)->execute($instanciaB, $this->vigilante);
    }
}
