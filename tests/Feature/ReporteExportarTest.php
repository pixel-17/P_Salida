<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Estado;
use App\Models\Motivo;
use App\Models\Papeleta;
use App\Models\Sede;
use App\Models\User;
use Database\Seeders\EstadoSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test de `reportes.exportar`: sin esto, un cambio en cualquiera de
 * las 4 hojas del Excel (ranking trabajadores/áreas/horas fuera/cuadro)
 * podía romper la respuesta y nadie se enteraba hasta que RRHH bajara el
 * reporte y lo encontrara corrupto o vacío.
 */
class ReporteExportarTest extends TestCase
{
    use RefreshDatabase;

    public function test_rrhh_puede_exportar_el_reporte_del_mes_actual(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(EstadoSeeder::class);

        $area = Area::create(['nombre' => 'Operaciones']);
        $sede = Sede::create(['nombre' => 'Sede Central']);
        $motivo = Motivo::create(['nombre' => 'Trámite personal']);
        $estado = Estado::where('codigo', 'FINALIZADO')->firstOrFail();

        $rrhh = User::factory()->create();
        $rrhh->assignRole('RRHH');

        $jefe = User::factory()->create(['sede_id' => $sede->id]);
        $jefe->assignRole('JEFE');

        $trabajador = User::factory()->create([
            'jefe_id' => $jefe->id,
            'sede_id' => $sede->id,
        ]);
        $trabajador->assignRole('TRABAJADOR');

        Papeleta::create([
            'codigo' => 'PAP-2026-00001',
            'trabajador_id' => $trabajador->id,
            'jefe_id' => $jefe->id,
            'area_id' => $area->id,
            'sede_id' => $sede->id,
            'motivo_id' => $motivo->id,
            'estado_id' => $estado->id,
            'destino' => 'Banco',
            'fecha_salida' => now()->toDateString(),
            'hora_salida_programada' => '09:00',
        ]);

        $response = $this->actingAs($rrhh)->get(route('reportes.exportar'));

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    public function test_rechaza_exportar_por_encima_del_limite_de_filas_del_reporte(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(EstadoSeeder::class);

        $area = Area::create(['nombre' => 'Operaciones']);
        $sede = Sede::create(['nombre' => 'Sede Central']);
        $motivo = Motivo::create(['nombre' => 'Trámite personal']);
        $estado = Estado::where('codigo', 'FINALIZADO')->firstOrFail();

        $rrhh = User::factory()->create();
        $rrhh->assignRole('RRHH');

        $trabajador = User::factory()->create(['sede_id' => $sede->id]);
        $trabajador->assignRole('TRABAJADOR');

        $limite = (new \ReflectionClass(\App\Http\Controllers\ReporteController::class))
            ->getConstant('MAX_FILAS_REPORTE');

        $ahora = now();
        collect(range(1, $limite + 1))
            ->map(fn ($i) => [
                'codigo' => 'PAP-'.uniqid('', true).'-'.$i,
                'trabajador_id' => $trabajador->id,
                'area_id' => $area->id,
                'sede_id' => $sede->id,
                'motivo_id' => $motivo->id,
                'estado_id' => $estado->id,
                'destino' => 'Banco',
                'fecha_salida' => $ahora->toDateString(),
                'hora_salida_programada' => '09:00',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])
            ->chunk(500)
            ->each(fn ($lote) => \Illuminate\Support\Facades\DB::table('papeletas')->insert($lote->all()));

        $response = $this->actingAs($rrhh)->get(route('reportes.exportar'));

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }
}
