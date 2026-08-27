<?php

namespace Tests\Feature\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UserPolicy no tenía ningún test, ni directo ni indirecto (a diferencia de
 * PapeletaPolicy, que sí quedaba ejercitada por los tests de Actions).
 * Cubre la relación jefe → trabajador de la pantalla "Mi equipo": el jefe
 * puede crear/ver/activar solo a SUS trabajadores pero nunca editar ni
 * eliminar; el administrador tiene el CRUD completo sobre cualquiera.
 */
class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;

    private User $admin;

    private User $jefe;

    private User $otroJefe;

    private User $trabajadorDelJefe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->policy = new UserPolicy;

        $this->admin = User::factory()->create();
        $this->admin->assignRole('ADMINISTRADOR');

        $this->jefe = User::factory()->create();
        $this->jefe->assignRole('JEFE');

        $this->otroJefe = User::factory()->create();
        $this->otroJefe->assignRole('JEFE');

        $this->trabajadorDelJefe = User::factory()->create(['jefe_id' => $this->jefe->id]);
        $this->trabajadorDelJefe->assignRole('TRABAJADOR');
    }

    // ---------- verEquipo() / crearTrabajador() ----------

    public function test_jefe_y_admin_pueden_ver_y_crear_en_su_equipo(): void
    {
        $this->assertTrue($this->policy->verEquipo($this->jefe));
        $this->assertTrue($this->policy->crearTrabajador($this->jefe));

        $this->assertTrue($this->policy->verEquipo($this->admin));
        $this->assertTrue($this->policy->crearTrabajador($this->admin));
    }

    public function test_rrhh_y_trabajador_no_tienen_acceso_a_mi_equipo(): void
    {
        $rrhh = User::factory()->create();
        $rrhh->assignRole('RRHH');

        $this->assertFalse($this->policy->verEquipo($rrhh));
        $this->assertFalse($this->policy->crearTrabajador($rrhh));
        $this->assertFalse($this->policy->verEquipo($this->trabajadorDelJefe));
        $this->assertFalse($this->policy->crearTrabajador($this->trabajadorDelJefe));
    }

    // ---------- verTrabajador() ----------

    public function test_el_jefe_ve_el_detalle_de_su_propio_trabajador(): void
    {
        $this->assertTrue($this->policy->verTrabajador($this->jefe, $this->trabajadorDelJefe));
    }

    public function test_el_jefe_no_ve_el_detalle_de_un_trabajador_ajeno(): void
    {
        $this->assertFalse($this->policy->verTrabajador($this->otroJefe, $this->trabajadorDelJefe));
    }

    public function test_el_admin_ve_el_detalle_de_cualquier_trabajador(): void
    {
        $this->assertTrue($this->policy->verTrabajador($this->admin, $this->trabajadorDelJefe));
    }

    // ---------- editarTrabajador() ----------

    public function test_ni_el_jefe_dueno_puede_editar_a_su_trabajador(): void
    {
        // Regla explícita del negocio: el jefe crea/activa/desactiva, pero
        // NUNCA edita ni elimina — ni siquiera a su propio trabajador.
        $this->assertFalse($this->policy->editarTrabajador($this->jefe, $this->trabajadorDelJefe));
    }

    public function test_solo_el_admin_puede_editar_trabajadores(): void
    {
        $this->assertTrue($this->policy->editarTrabajador($this->admin, $this->trabajadorDelJefe));
    }

    // ---------- activarTrabajador() ----------

    public function test_el_jefe_dueno_puede_activar_o_desactivar_a_su_trabajador(): void
    {
        $this->assertTrue($this->policy->activarTrabajador($this->jefe, $this->trabajadorDelJefe));
    }

    public function test_un_jefe_no_dueno_no_puede_activar_un_trabajador_ajeno(): void
    {
        $this->assertFalse($this->policy->activarTrabajador($this->otroJefe, $this->trabajadorDelJefe));
    }

    public function test_el_admin_puede_activar_cualquier_trabajador(): void
    {
        $this->assertTrue($this->policy->activarTrabajador($this->admin, $this->trabajadorDelJefe));
    }

    // ---------- eliminarTrabajador() ----------

    public function test_ni_el_jefe_dueno_puede_eliminar_a_su_trabajador(): void
    {
        $this->assertFalse($this->policy->eliminarTrabajador($this->jefe, $this->trabajadorDelJefe));
    }

    public function test_solo_el_admin_puede_eliminar_trabajadores(): void
    {
        $this->assertTrue($this->policy->eliminarTrabajador($this->admin, $this->trabajadorDelJefe));
    }
}
