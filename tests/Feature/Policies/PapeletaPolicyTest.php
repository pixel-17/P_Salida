<?php

namespace Tests\Feature\Policies;

use App\Enums\TipoObservacion;
use App\Models\Adjunto;
use App\Models\Observacion;
use App\Models\User;
use App\Policies\PapeletaPolicy;
use Tests\Support\PapeletaActionTestCase;

/**
 * Los demás métodos de PapeletaPolicy (decidir, marcarComoVigilante,
 * cancelar, eliminarAdjunto, verCodigo, responderObservacion) ya quedan
 * ejercitados indirectamente por los tests de Actions y por
 * AdjuntoDestroyTest. Este archivo cubre los 4 que no tenían ningún test,
 * directo ni indirecto: ver, crear, marcar y adjuntar.
 */
class PapeletaPolicyTest extends PapeletaActionTestCase
{
    private PapeletaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PapeletaPolicy;
    }

    // ---------- ver() ----------

    public function test_el_trabajador_dueno_puede_ver_su_papeleta(): void
    {
        $papeleta = $this->crearPapeleta();

        $this->assertTrue($this->policy->ver($this->trabajador, $papeleta));
    }

    public function test_el_jefe_asignado_puede_ver_la_papeleta(): void
    {
        $papeleta = $this->crearPapeleta();

        $this->assertTrue($this->policy->ver($this->jefe, $papeleta));
    }

    public function test_rrhh_puede_ver_cualquier_papeleta(): void
    {
        $papeleta = $this->crearPapeleta();

        $this->assertTrue($this->policy->ver($this->rrhh, $papeleta));
    }

    public function test_un_trabajador_ajeno_no_puede_ver_la_papeleta_de_otro(): void
    {
        $papeleta = $this->crearPapeleta();

        $otro = User::factory()->create(['sede_id' => $this->sede->id]);
        $otro->assignRole('TRABAJADOR');

        $this->assertFalse($this->policy->ver($otro, $papeleta));
    }

    public function test_un_jefe_no_asignado_no_puede_ver_la_papeleta(): void
    {
        $papeleta = $this->crearPapeleta();

        $otroJefe = User::factory()->create(['sede_id' => $this->sede->id]);
        $otroJefe->assignRole('JEFE');

        $this->assertFalse($this->policy->ver($otroJefe, $papeleta));
    }

    // ---------- crear() ----------

    public function test_trabajador_puede_crear_papeleta(): void
    {
        $this->assertTrue($this->policy->crear($this->trabajador));
    }

    public function test_jefe_puede_crear_papeleta_para_si_mismo(): void
    {
        $this->assertTrue($this->policy->crear($this->jefe));
    }

    public function test_rrhh_no_puede_crear_papeleta(): void
    {
        $this->assertFalse($this->policy->crear($this->rrhh));
    }

    public function test_vigilante_no_puede_crear_papeleta(): void
    {
        $this->assertFalse($this->policy->crear($this->vigilante));
    }

    // ---------- marcar() ----------

    public function test_marcar_siempre_es_false_sin_importar_el_rol(): void
    {
        // Ver el docblock del método: el trabajador NUNCA marca su propia
        // salida/retorno, eso es exclusivo del vigilante vía
        // marcarComoVigilante(). Se deja el método (siempre false) para que
        // cualquier código viejo que lo consulte falle cerrado.
        $papeleta = $this->crearPapeleta('APROBADO_RRHH');

        $this->assertFalse($this->policy->marcar($this->trabajador, $papeleta));
        $this->assertFalse($this->policy->marcar($this->vigilante, $papeleta));
        $this->assertFalse($this->policy->marcar($this->rrhh, $papeleta));
    }

    // ---------- adjuntar() ----------

    public function test_el_trabajador_puede_adjuntar_si_el_motivo_requiere_documento_y_no_tiene_ninguno(): void
    {
        $this->motivo->update(['requiere_documento' => true]);
        $papeleta = $this->crearPapeleta('SOLICITADO');

        $this->assertTrue($this->policy->adjuntar($this->trabajador, $papeleta));
    }

    public function test_el_trabajador_no_puede_adjuntar_si_el_motivo_no_requiere_documento(): void
    {
        $this->motivo->update(['requiere_documento' => false]);
        $papeleta = $this->crearPapeleta('SOLICITADO');

        $this->assertFalse($this->policy->adjuntar($this->trabajador, $papeleta));
    }

    public function test_el_trabajador_no_puede_adjuntar_de_nuevo_si_ya_tiene_documento_y_no_hay_observacion_pendiente(): void
    {
        $this->motivo->update(['requiere_documento' => true]);
        $papeleta = $this->crearPapeleta('SOLICITADO');

        Adjunto::create([
            'papeleta_id' => $papeleta->id,
            'nombre_original' => 'sustento.pdf',
            'archivo' => "papeletas/{$papeleta->id}/sustento.pdf",
            'extension' => 'pdf',
            'peso' => 1024,
        ]);
        $papeleta->unsetRelation('adjuntos');

        $this->assertFalse($this->policy->adjuntar($this->trabajador, $papeleta));
    }

    public function test_el_trabajador_puede_adjuntar_de_nuevo_si_hay_una_observacion_de_justificacion_pendiente(): void
    {
        // Aunque el motivo NO exija documento y ya tenga uno, una
        // observación JUSTIFICACION sin atender reabre la posibilidad de
        // subir otro archivo — es justo lo que la observación está pidiendo.
        $this->motivo->update(['requiere_documento' => false]);
        $papeleta = $this->crearPapeleta('OBSERVADO');

        Observacion::create([
            'papeleta_id' => $papeleta->id,
            'usuario_id' => $this->jefe->id,
            'tipo' => TipoObservacion::JUSTIFICACION->value,
            'comentario' => 'Falta sustento del destino.',
            'atendida' => false,
        ]);
        $papeleta->unsetRelation('observaciones');

        $this->assertTrue($this->policy->adjuntar($this->trabajador, $papeleta));
    }

    public function test_un_tercero_no_puede_adjuntar_a_la_papeleta_de_otro(): void
    {
        $this->motivo->update(['requiere_documento' => true]);
        $papeleta = $this->crearPapeleta('SOLICITADO');

        $this->assertFalse($this->policy->adjuntar($this->jefe, $papeleta));
    }
}
