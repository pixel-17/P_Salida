<?php

namespace Tests\Feature;

use App\Http\Controllers\ReporteController;
use App\Models\Area;
use App\Models\Estado;
use App\Models\Motivo;
use App\Models\Papeleta;
use App\Models\Sede;
use App\Models\User;
use Database\Seeders\EstadoSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

class PapeletaExportarTest extends TestCase
{
    use RefreshDatabase;

    private function crearEntorno(): array
    {
        $this->seed(RoleSeeder::class);
        $this->seed(EstadoSeeder::class);

        $area = Area::create(['nombre' => 'Operaciones']);
        $sede = Sede::create(['nombre' => 'Sede Central']);
        $motivo = Motivo::create(['nombre' => 'Trámite personal']);
        $estado = Estado::where('codigo', 'SOLICITADO')->firstOrFail();

        $rrhh = User::factory()->create();
        $rrhh->assignRole('RRHH');

        $trabajador = User::factory()->create();
        $trabajador->assignRole('TRABAJADOR');

        return compact('area', 'sede', 'motivo', 'estado', 'rrhh', 'trabajador');
    }

    /**
     * Inserta papeletas en bloque (sin pasar por Eloquent) para poder
     * generar miles de filas en el test del límite sin que sea lentísimo.
     */
    private function crearPapeletasEnBloque(int $cantidad, array $entorno): void
    {
        $ahora = now();

        collect(range(1, $cantidad))
            ->map(fn ($i) => [
                'codigo' => 'PAP-'.uniqid('', true).'-'.$i,
                'trabajador_id' => $entorno['trabajador']->id,
                'area_id' => $entorno['area']->id,
                'sede_id' => $entorno['sede']->id,
                'motivo_id' => $entorno['motivo']->id,
                'estado_id' => $entorno['estado']->id,
                'destino' => 'Banco',
                'fecha_salida' => $ahora->toDateString(),
                'hora_salida_programada' => '09:00',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])
            ->chunk(500)
            ->each(fn ($lote) => DB::table('papeletas')->insert($lote->all()));
    }

    public function test_rrhh_puede_exportar_cuando_esta_por_debajo_del_limite(): void
    {
        $entorno = $this->crearEntorno();
        $this->crearPapeletasEnBloque(3, $entorno);

        $response = $this->actingAs($entorno['rrhh'])->get(route('reportes.exportar', ['vista' => 'todas']));

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    public function test_la_exportacion_se_rechaza_por_encima_del_limite_configurado(): void
    {
        $entorno = $this->crearEntorno();

        $limite = (new ReflectionClass(ReporteController::class))->getConstant('MAX_FILAS_REPORTE');

        $this->crearPapeletasEnBloque($limite + 1, $entorno);

        $this->assertSame($limite + 1, Papeleta::count());

        // papeletasDelRango() filtra por fecha_salida dentro de
        // desde/hasta (por defecto: mes en curso); crearPapeletasEnBloque
        // ya deja fecha_salida = hoy, así que todas caen en rango sin
        // pasar parámetros extra.
        $response = $this->actingAs($entorno['rrhh'])->get(route('reportes.exportar', ['vista' => 'todas']));

        $response->assertRedirect();
        $response->assertSessionHasErrors('reporte');
    }
}
