<?php

namespace Tests\Feature\Actions;

use App\Actions\AprobarPapeletaAction;
use App\Enums\RolUsuario;
use App\Models\Estado;
use App\Models\FlujoAprobacion;
use App\Models\Papeleta;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\Support\PapeletaActionTestCase;

class AprobarPapeletaActionTest extends PapeletaActionTestCase
{
    public function test_el_jefe_aprueba_y_pasa_a_aprobado_jefe(): void
    {
        $papeleta = $this->crearPapeleta('SOLICITADO');

        (new AprobarPapeletaAction)->execute($papeleta, $this->jefe);

        $papeleta->refresh();
        $this->assertSame('APROBADO_JEFE', $papeleta->estado->codigo);

        $this->assertDatabaseHas('flujo_aprobaciones', [
            'papeleta_id' => $papeleta->id,
            'usuario_id' => $this->jefe->id,
            'rol' => RolUsuario::JEFE->value,
            'accion' => 'APROBADO',
        ]);
    }

    public function test_rrhh_aprueba_y_pasa_a_aprobado_rrhh(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_JEFE');

        (new AprobarPapeletaAction)->execute($papeleta, $this->rrhh);

        $papeleta->refresh();
        $this->assertSame('APROBADO_RRHH', $papeleta->estado->codigo);

        $this->assertDatabaseHas('flujo_aprobaciones', [
            'papeleta_id' => $papeleta->id,
            'usuario_id' => $this->rrhh->id,
            'rol' => RolUsuario::RRHH->value,
            'accion' => 'APROBADO',
        ]);
    }

    public function test_un_jefe_distinto_al_asignado_no_puede_aprobar(): void
    {
        $papeleta = $this->crearPapeleta('SOLICITADO');
        $otroJefe = User::factory()->create(['sede_id' => $this->sede->id]);
        $otroJefe->assignRole('JEFE');

        $this->expectException(AuthorizationException::class);

        (new AprobarPapeletaAction)->execute($papeleta, $otroJefe);
    }

    /**
     * Caso de carrera: la papeleta ya fue aprobada por RRHH (por ejemplo un
     * segundo clic que llegó después, o una segunda pestaña) y alguien
     * vuelve a intentar aprobarla como si siguiera en APROBADO_JEFE. Antes
     * del lockForUpdate esto igual fallaba porque decidir() ya evaluaba el
     * estado en memoria, pero sin el lock dos requests concurrentes podían
     * leer el mismo estado "viejo" y ambas pasar el chequeo, generando dos
     * FlujoAprobacion. Este test fija el comportamiento esperado: una vez
     * que el estado ya cambió, cualquier intento posterior debe fallar.
     */
    public function test_no_se_puede_reaprobar_una_papeleta_que_ya_cambio_de_estado(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_JEFE');

        (new AprobarPapeletaAction)->execute($papeleta, $this->rrhh);

        // $papeleta (la instancia original, no recargada) sigue "creyendo"
        // que el estado es APROBADO_JEFE si no se hace refresh — simula la
        // foto que tendría un segundo worker/request que leyó el modelo
        // antes de que el primero terminara.
        $papeletaDesactualizada = Papeleta::find($papeleta->id);
        $papeletaDesactualizada->setRelation('estado', Estado::where('codigo', 'APROBADO_JEFE')->first());

        $this->expectException(AuthorizationException::class);

        (new AprobarPapeletaAction)->execute($papeletaDesactualizada, $this->rrhh);
    }

    public function test_no_se_duplica_flujo_aprobacion_al_reintentar_sobre_estado_ya_cambiado(): void
    {
        $papeleta = $this->crearPapeleta('SOLICITADO');

        (new AprobarPapeletaAction)->execute($papeleta, $this->jefe);

        try {
            // Mismo jefe, misma instancia sin refresh, intenta aprobar de nuevo.
            (new AprobarPapeletaAction)->execute($papeleta, $this->jefe);
        } catch (AuthorizationException) {
            // Esperado: decidir() para SOLICITADO ya no aplica una vez en
            // APROBADO_JEFE.
        }

        $this->assertSame(
            1,
            FlujoAprobacion::where('papeleta_id', $papeleta->id)->count(),
            'Solo debe existir un registro de aprobación, no uno por cada intento.'
        );
    }
}
