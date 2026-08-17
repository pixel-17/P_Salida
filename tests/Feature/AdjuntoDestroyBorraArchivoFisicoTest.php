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

/**
 * Antes de la corrección, destroy() solo borraba la fila `adjuntos`: el
 * archivo físico en storage/app quedaba huérfano para siempre. Este test
 * fija que ahora el archivo también se borra del disco.
 */
class AdjuntoDestroyBorraArchivoFisicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_al_eliminar_un_adjunto_tambien_se_borra_el_archivo_del_disco(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\EstadoSeeder::class);

        Storage::fake('local');

        $area = Area::create(['nombre' => 'Operaciones']);
        $sede = Sede::create(['nombre' => 'Sede Central']);
        $motivo = Motivo::create(['nombre' => 'Trámite personal', 'requiere_documento' => true]);
        $estado = Estado::where('codigo', 'SOLICITADO')->firstOrFail();

        $jefe = User::factory()->create();
        $jefe->assignRole('JEFE');

        $trabajador = User::factory()->create(['jefe_id' => $jefe->id]);
        $trabajador->assignRole('TRABAJADOR');

        $papeleta = Papeleta::create([
            'codigo' => 'PAP-2026-00002',
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

        $archivo = UploadedFile::fake()->create('sustento.pdf', 100, 'application/pdf');
        $ruta = $archivo->store("papeletas/{$papeleta->id}", 'local');

        $adjunto = Adjunto::create([
            'papeleta_id' => $papeleta->id,
            'nombre_original' => 'sustento.pdf',
            'archivo' => $ruta,
            'extension' => 'pdf',
            'peso' => 1024,
        ]);

        Storage::disk('local')->assertExists($ruta);

        $this->actingAs($trabajador)->delete(route('adjuntos.destroy', $adjunto));

        $this->assertModelMissing($adjunto);
        Storage::disk('local')->assertMissing($ruta);
    }
}
