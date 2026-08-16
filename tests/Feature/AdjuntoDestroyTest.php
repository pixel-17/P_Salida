<?php

namespace Tests\Feature;

use App\Models\Adjunto;
use App\Models\Area;
use App\Models\Estado;
use App\Models\Motivo;
use App\Models\Papeleta;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdjuntoDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea una papeleta lista para usar en los tests, con su trabajador,
     * jefe y adjunto ya subido.
     */
    private function crearPapeletaConAdjunto(string $estadoCodigo = 'SOLICITADO'): array
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\EstadoSeeder::class);

        $area = Area::create(['nombre' => 'Operaciones']);
        $sede = Sede::create(['nombre' => 'Sede Central']);
        $motivo = Motivo::create(['nombre' => 'Trámite personal', 'requiere_documento' => true]);
        $estado = Estado::where('codigo', $estadoCodigo)->firstOrFail();

        $jefe = User::factory()->create();
        $jefe->assignRole('JEFE');

        $trabajador = User::factory()->create(['jefe_id' => $jefe->id]);
        $trabajador->assignRole('TRABAJADOR');

        $papeleta = Papeleta::create([
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

        Storage::fake('local');
        $archivo = UploadedFile::fake()->create('sustento.pdf', 100, 'application/pdf');
        $ruta = $archivo->store("papeletas/{$papeleta->id}", 'local');

        $adjunto = Adjunto::create([
            'papeleta_id' => $papeleta->id,
            'nombre_original' => 'sustento.pdf',
            'archivo' => $ruta,
            'extension' => 'pdf',
            'peso' => 1024,
        ]);

        return compact('papeleta', 'adjunto', 'trabajador', 'jefe');
    }

    public function test_el_jefe_no_puede_eliminar_un_adjunto_del_trabajador(): void
    {
        // Antes de la corrección, la ruta usaba la policy 'ver' (que el jefe
        // sí cumple) en vez de una policy dedicada de borrado. Este test
        // cubre exactamente ese caso: el jefe puede VER la papeleta pero no
        // debe poder ELIMINAR sus adjuntos.
        ['adjunto' => $adjunto, 'jefe' => $jefe] = $this->crearPapeletaConAdjunto();

        $response = $this->actingAs($jefe)->delete(route('adjuntos.destroy', $adjunto));

        $response->assertForbidden();
        $this->assertModelExists($adjunto);
    }

    public function test_rrhh_no_puede_eliminar_un_adjunto_del_trabajador(): void
    {
        ['adjunto' => $adjunto] = $this->crearPapeletaConAdjunto();

        $rrhh = User::factory()->create();
        $rrhh->assignRole('RRHH');

        $response = $this->actingAs($rrhh)->delete(route('adjuntos.destroy', $adjunto));

        $response->assertForbidden();
        $this->assertModelExists($adjunto);
    }

    public function test_el_trabajador_puede_eliminar_su_propio_adjunto_mientras_esta_solicitado(): void
    {
        ['adjunto' => $adjunto, 'trabajador' => $trabajador] = $this->crearPapeletaConAdjunto('SOLICITADO');

        $response = $this->actingAs($trabajador)->delete(route('adjuntos.destroy', $adjunto));

        $response->assertRedirect();
        $this->assertModelMissing($adjunto);
    }

    public function test_el_trabajador_no_puede_eliminar_el_adjunto_una_vez_aprobado_por_rrhh(): void
    {
        ['adjunto' => $adjunto, 'trabajador' => $trabajador] = $this->crearPapeletaConAdjunto('APROBADO_RRHH');

        $response = $this->actingAs($trabajador)->delete(route('adjuntos.destroy', $adjunto));

        $response->assertForbidden();
        $this->assertModelExists($adjunto);
    }

    public function test_otro_trabajador_ajeno_no_puede_eliminar_el_adjunto(): void
    {
        ['adjunto' => $adjunto] = $this->crearPapeletaConAdjunto();

        $otro = User::factory()->create();
        $otro->assignRole('TRABAJADOR');

        $response = $this->actingAs($otro)->delete(route('adjuntos.destroy', $adjunto));

        $response->assertForbidden();
        $this->assertModelExists($adjunto);
    }

    /**
     * Caso de evidencia: un adjunto subido como respuesta a una observación
     * JUSTIFICACION no se puede eliminar aunque la papeleta esté en un
     * estado (SOLICITADO) donde otros adjuntos sí serían borrables. Esto
     * cubre el gap donde antes solo se validaba el estado, sin importar si
     * el archivo era evidencia de una observación ya respondida.
     */
    public function test_el_trabajador_no_puede_eliminar_un_adjunto_que_es_evidencia_de_una_observacion(): void
    {
        ['papeleta' => $papeleta, 'trabajador' => $trabajador, 'jefe' => $jefe]
            = $this->crearPapeletaConAdjunto('SOLICITADO');

        $observacion = \App\Models\Observacion::create([
            'papeleta_id' => $papeleta->id,
            'usuario_id' => $jefe->id,
            'tipo' => 'JUSTIFICACION',
            'comentario' => 'Falta sustento del destino.',
            'atendida' => true,
        ]);

        $adjuntoEvidencia = Adjunto::create([
            'papeleta_id' => $papeleta->id,
            'observacion_id' => $observacion->id,
            'nombre_original' => 'sustento_respuesta.pdf',
            'archivo' => "papeletas/{$papeleta->id}/sustento_respuesta.pdf",
            'extension' => 'pdf',
            'peso' => 1024,
        ]);

        // La papeleta sigue en SOLICITADO (mismo estado del setUp), donde
        // un adjunto normal SÍ sería borrable — ver el test de arriba.
        $response = $this->actingAs($trabajador)->delete(route('adjuntos.destroy', $adjuntoEvidencia));

        $response->assertForbidden();
        $this->assertModelExists($adjuntoEvidencia);
    }
}
