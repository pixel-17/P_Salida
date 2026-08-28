<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Papeleta;
use Illuminate\Support\Carbon;
use Tests\Feature\Actions\PapeletaActionTestCase;

/**
 * PapeletaController no tenía test propio más allá de exportar() (ver
 * PapeletaExportarTest). Cubre store() (creación real vía HTTP, con las
 * validaciones cruzadas de StorePapeletaRequest: día no laborable, horario
 * laboral), index() (alcance por rol) y cancelar().
 */
class PapeletaControllerTest extends PapeletaActionTestCase
{
    /**
     * Próximo lunes: garantiza fecha futura y día laborable (domingo=0 es
     * el único no laborable por defecto, ver Configuracion::diasNoLaborables())
     * sin importar qué día sea "hoy" cuando corra el test.
     */
    private function proximoLunes(): Carbon
    {
        return now()->next(Carbon::MONDAY);
    }

    // ---------- store() ----------

    public function test_el_trabajador_crea_una_papeleta_valida(): void
    {
        $cargo = Cargo::create(['area_id' => $this->area->id, 'nombre' => 'Analista']);
        $this->trabajador->update(['cargo_id' => $cargo->id]);

        $response = $this->actingAs($this->trabajador)->post(route('papeletas.store'), [
            'motivo_id' => $this->motivo->id,
            'destino' => 'Banco de la Nación',
            'fecha_salida' => $this->proximoLunes()->toDateString(),
            'hora_salida_programada' => '10:00',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('papeletas', [
            'trabajador_id' => $this->trabajador->id,
            'jefe_id' => $this->jefe->id,
            'area_id' => $this->area->id,
            'destino' => 'Banco de la Nación',
        ]);
    }

    public function test_rrhh_no_puede_crear_papeletas(): void
    {
        $response = $this->actingAs($this->rrhh)->post(route('papeletas.store'), [
            'motivo_id' => $this->motivo->id,
            'destino' => 'Banco',
            'fecha_salida' => $this->proximoLunes()->toDateString(),
            'hora_salida_programada' => '10:00',
        ]);

        $response->assertForbidden();
        $this->assertSame(0, Papeleta::count());
    }

    public function test_no_se_puede_crear_papeleta_en_domingo(): void
    {
        $cargo = Cargo::create(['area_id' => $this->area->id, 'nombre' => 'Analista']);
        $this->trabajador->update(['cargo_id' => $cargo->id]);

        $domingo = now()->next(Carbon::SUNDAY);

        $response = $this->actingAs($this->trabajador)->post(route('papeletas.store'), [
            'motivo_id' => $this->motivo->id,
            'destino' => 'Banco',
            'fecha_salida' => $domingo->toDateString(),
            'hora_salida_programada' => '10:00',
        ]);

        $response->assertSessionHasErrors('fecha_salida');
        $this->assertSame(0, Papeleta::count());
    }

    public function test_no_se_puede_crear_papeleta_fuera_del_horario_laboral(): void
    {
        $cargo = Cargo::create(['area_id' => $this->area->id, 'nombre' => 'Analista']);
        $this->trabajador->update(['cargo_id' => $cargo->id]);

        $response = $this->actingAs($this->trabajador)->post(route('papeletas.store'), [
            'motivo_id' => $this->motivo->id,
            'destino' => 'Banco',
            'fecha_salida' => $this->proximoLunes()->toDateString(),
            'hora_salida_programada' => '20:30', // fuera de 07:00-19:00
        ]);

        $response->assertSessionHasErrors('hora_salida_programada');
        $this->assertSame(0, Papeleta::count());
    }

    // ---------- index() ----------

    public function test_el_trabajador_solo_ve_sus_propias_papeletas_en_la_bandeja(): void
    {
        $propia = $this->crearPapeleta('SOLICITADO');

        $otroTrabajador = \App\Models\User::factory()->create([
            'jefe_id' => $this->jefe->id,
            'sede_id' => $this->sede->id,
        ]);
        $otroTrabajador->assignRole('TRABAJADOR');
        $ajena = $this->crearPapeleta('SOLICITADO', ['trabajador_id' => $otroTrabajador->id]);

        $response = $this->actingAs($this->trabajador)->get(route('papeletas.index'));

        $response->assertOk();
        $response->assertViewHas('papeletas', function ($papeletas) use ($propia, $ajena) {
            $ids = collect($papeletas->items())->pluck('id');

            return $ids->contains($propia->id) && ! $ids->contains($ajena->id);
        });
    }

    public function test_el_jefe_con_vista_todas_ve_solo_a_su_equipo(): void
    {
        $deSuEquipo = $this->crearPapeleta('SOLICITADO');

        $otroJefe = \App\Models\User::factory()->create(['sede_id' => $this->sede->id]);
        $otroJefe->assignRole('JEFE');
        $otroTrabajador = \App\Models\User::factory()->create([
            'jefe_id' => $otroJefe->id,
            'sede_id' => $this->sede->id,
        ]);
        $otroTrabajador->assignRole('TRABAJADOR');
        $deOtroEquipo = $this->crearPapeleta('SOLICITADO', ['trabajador_id' => $otroTrabajador->id, 'jefe_id' => $otroJefe->id]);

        $response = $this->actingAs($this->jefe)->get(route('papeletas.index', ['vista' => 'todas']));

        $response->assertOk();
        $response->assertViewHas('papeletas', function ($papeletas) use ($deSuEquipo, $deOtroEquipo) {
            $ids = collect($papeletas->items())->pluck('id');

            return $ids->contains($deSuEquipo->id) && ! $ids->contains($deOtroEquipo->id);
        });
    }

    // ---------- cancelar() ----------

    public function test_el_trabajador_cancela_su_papeleta_con_motivo_valido(): void
    {
        $papeleta = $this->crearPapeleta('SOLICITADO');

        $response = $this->actingAs($this->trabajador)->post(route('papeletas.cancelar', $papeleta), [
            'motivo' => 'Ya no necesito hacer el trámite.',
        ]);

        $response->assertRedirect(route('papeletas.show', $papeleta));
        $this->assertSame('CANCELADO', $papeleta->fresh()->estado->codigo);
    }

    public function test_no_se_puede_cancelar_con_un_motivo_demasiado_corto(): void
    {
        $papeleta = $this->crearPapeleta('SOLICITADO');

        $response = $this->actingAs($this->trabajador)->post(route('papeletas.cancelar', $papeleta), [
            'motivo' => 'no',
        ]);

        $response->assertSessionHasErrors('motivo');
        $this->assertSame('SOLICITADO', $papeleta->fresh()->estado->codigo);
    }

    public function test_un_tercero_no_puede_cancelar_la_papeleta_de_otro(): void
    {
        $papeleta = $this->crearPapeleta('SOLICITADO');

        $response = $this->actingAs($this->rrhh)->post(route('papeletas.cancelar', $papeleta), [
            'motivo' => 'Intento de cancelación ajena.',
        ]);

        $response->assertForbidden();
        $this->assertSame('SOLICITADO', $papeleta->fresh()->estado->codigo);
    }
}
