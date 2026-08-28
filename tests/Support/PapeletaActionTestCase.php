<?php

namespace Tests\Support;

use App\Enums\TipoMarcacion;
use App\Models\Area;
use App\Models\Estado;
use App\Models\Marcacion;
use App\Models\Motivo;
use App\Models\Papeleta;
use App\Models\Sede;
use App\Models\User;
use Database\Seeders\EstadoSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Base común para los tests de las Actions del flujo de papeletas. Arma el
 * escenario mínimo (roles, estados, sede, área, motivo, jefe, trabajador,
 * vigilante) para no repetirlo en cada archivo de test.
 */
abstract class PapeletaActionTestCase extends TestCase
{
    use RefreshDatabase;

    protected Sede $sede;

    protected Area $area;

    protected Motivo $motivo;

    protected User $jefe;

    protected User $trabajador;

    protected User $vigilante;

    protected User $rrhh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(EstadoSeeder::class);

        $this->sede = Sede::create(['nombre' => 'Sede Central']);
        $this->area = Area::create(['nombre' => 'Operaciones']);
        $this->motivo = Motivo::create(['nombre' => 'Trámite personal', 'requiere_documento' => false]);

        $this->jefe = User::factory()->create(['sede_id' => $this->sede->id]);
        $this->jefe->assignRole('JEFE');

        $this->trabajador = User::factory()->create([
            'jefe_id' => $this->jefe->id,
            'sede_id' => $this->sede->id,
        ]);
        $this->trabajador->assignRole('TRABAJADOR');

        $this->vigilante = User::factory()->create(['sede_id' => $this->sede->id]);
        $this->vigilante->assignRole('VIGILANTE');

        $this->rrhh = User::factory()->create(['sede_id' => $this->sede->id]);
        $this->rrhh->assignRole('RRHH');
    }

    /**
     * Crea una papeleta en el estado indicado (por defecto SOLICITADO), lista
     * para pasarle a cualquiera de las Actions.
     */
    protected function crearPapeleta(string $estadoCodigo = 'SOLICITADO', array $overrides = []): Papeleta
    {
        $estado = Estado::where('codigo', $estadoCodigo)->firstOrFail();

        return Papeleta::create(array_merge([
            'codigo' => 'PAP-2026-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'trabajador_id' => $this->trabajador->id,
            'jefe_id' => $this->jefe->id,
            'area_id' => $this->area->id,
            'sede_id' => $this->sede->id,
            'motivo_id' => $this->motivo->id,
            'estado_id' => $estado->id,
            'destino' => 'Banco',
            'fecha_salida' => now()->toDateString(),
            'hora_salida_programada' => '09:00',
        ], $overrides));
    }

    protected function marcarSalida(Papeleta $papeleta): Marcacion
    {
        return Marcacion::create([
            'papeleta_id' => $papeleta->id,
            'tipo' => TipoMarcacion::SALIDA->value,
            'registrado_por_user_id' => $this->vigilante->id,
        ]);
    }
}
