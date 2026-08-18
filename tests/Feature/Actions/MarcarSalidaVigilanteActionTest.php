<?php

namespace Tests\Feature\Actions;

use App\Actions\MarcarSalidaVigilanteAction;
use App\Enums\TipoMarcacion;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class MarcarSalidaVigilanteActionTest extends PapeletaActionTestCase
{
    public function test_el_vigilante_marca_salida_y_la_papeleta_pasa_a_en_curso(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_RRHH');

        (new MarcarSalidaVigilanteAction)->execute($papeleta, $this->vigilante);

        $papeleta->refresh();
        $this->assertSame('EN_CURSO', $papeleta->estado->codigo);
        $this->assertDatabaseHas('marcaciones', [
            'papeleta_id' => $papeleta->id,
            'tipo' => TipoMarcacion::SALIDA->value,
            'registrado_por_user_id' => $this->vigilante->id,
        ]);
    }

    public function test_un_vigilante_de_otra_sede_no_puede_marcar(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_RRHH');

        $otraSede = Sede::create(['nombre' => 'Sede Norte']);
        $vigilanteOtraSede = User::factory()->create(['sede_id' => $otraSede->id]);
        $vigilanteOtraSede->assignRole('VIGILANTE');

        $this->expectException(AuthorizationException::class);

        (new MarcarSalidaVigilanteAction)->execute($papeleta, $vigilanteOtraSede);
    }

    public function test_no_se_puede_marcar_salida_dos_veces(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_RRHH');

        (new MarcarSalidaVigilanteAction)->execute($papeleta, $this->vigilante);
        $papeleta->refresh();

        $this->expectException(ValidationException::class);

        (new MarcarSalidaVigilanteAction)->execute($papeleta, $this->vigilante);
    }

    public function test_no_se_puede_marcar_salida_antes_de_la_fecha_autorizada(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
            'fecha_salida' => now()->addDays(3)->toDateString(),
        ]);

        $this->expectException(ValidationException::class);

        (new MarcarSalidaVigilanteAction)->execute($papeleta, $this->vigilante);
    }

    public function test_no_se_puede_marcar_salida_despues_de_la_fecha_autorizada(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
            'fecha_salida' => now()->subDay()->toDateString(),
        ]);

        $this->expectException(ValidationException::class);

        (new MarcarSalidaVigilanteAction)->execute($papeleta, $this->vigilante);
    }

    /**
     * Simula la carrera real: dos "workers" que leyeron la papeleta ANTES de
     * que cualquiera de los dos marcara salida (dos instancias de Eloquent
     * independientes, ninguna con el estado post-marcación). Con
     * lockForUpdate(), la segunda ejecución espera a que la primera
     * transacción termine y relee `marcaciones` ya con la fila creada, así
     * que revienta con ValidationException y NO con un 500 por el UNIQUE de
     * BD ni con una segunda fila.
     */
    public function test_doble_marcacion_simultanea_no_duplica_ni_revienta_por_sql(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_RRHH');

        $instanciaA = \App\Models\Papeleta::find($papeleta->id);
        $instanciaB = \App\Models\Papeleta::find($papeleta->id);

        (new MarcarSalidaVigilanteAction)->execute($instanciaA, $this->vigilante);

        $this->expectException(ValidationException::class);

        (new MarcarSalidaVigilanteAction)->execute($instanciaB, $this->vigilante);
    }
}
