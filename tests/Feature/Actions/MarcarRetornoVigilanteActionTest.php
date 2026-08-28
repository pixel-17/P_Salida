<?php

namespace Tests\Feature\Actions;

use App\Actions\MarcarRetornoVigilanteAction;
use App\Enums\TipoMarcacion;
use App\Models\Papeleta;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Tests\Support\PapeletaActionTestCase;

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

        // La primera marcación ya dejó la papeleta en FINALIZADO, así que la
        // Policy (marcarComoVigilante exige APROBADO_RRHH o EN_CURSO) la
        // bloquea antes de llegar al chequeo interno de "ya tiene retorno"
        // — ese chequeo interno sigue vivo para la carrera concurrente, ver
        // test_doble_marcacion_de_retorno_simultanea_no_duplica.
        $this->expectException(AuthorizationException::class);

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

        $instanciaA = Papeleta::find($papeleta->id);
        $instanciaB = Papeleta::find($papeleta->id);

        (new MarcarRetornoVigilanteAction)->execute($instanciaA, $this->vigilante);

        $this->expectException(ValidationException::class);

        (new MarcarRetornoVigilanteAction)->execute($instanciaB, $this->vigilante);
    }
}
