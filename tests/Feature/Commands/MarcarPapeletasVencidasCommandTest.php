<?php

namespace Tests\Feature\Commands;

use App\Enums\TipoMarcacion;
use App\Models\Marcacion;
use App\Models\Papeleta;
use App\Notifications\PapeletaVencidaNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Support\PapeletaActionTestCase;

/**
 * Cubre `papeletas:marcar-vencidas`. Es el comando que la request nunca
 * dispara por sí sola (solo corre por Schedule::command en
 * routes/console.php), así que sin estos tests una regresión aquí no se
 * detecta hasta que RRHH nota papeletas EN_CURSO que nunca cerraron.
 */
class MarcarPapeletasVencidasCommandTest extends PapeletaActionTestCase
{
    public function test_papeleta_en_curso_con_hora_retorno_programada_vencida_pasa_a_vencida(): void
    {
        Notification::fake();

        $papeleta = $this->crearPapeleta('EN_CURSO', [
            'fecha_salida' => now()->subDay()->toDateString(),
            'hora_retorno_programada' => '18:00',
        ]);
        $this->marcarSalida($papeleta);

        $this->artisan('papeletas:marcar-vencidas')->assertSuccessful();

        $papeleta->refresh();
        $this->assertSame('VENCIDA', $papeleta->estado->codigo);
        $this->assertDatabaseHas('historial_papeletas', [
            'papeleta_id' => $papeleta->id,
            'accion' => 'VENCIDA_AUTOMATICA',
        ]);
        Notification::assertSentTo($this->trabajador, PapeletaVencidaNotification::class);
        Notification::assertSentTo($this->jefe, PapeletaVencidaNotification::class);
    }

    public function test_papeleta_en_curso_con_hora_retorno_programada_futura_no_se_toca(): void
    {
        $papeleta = $this->crearPapeleta('EN_CURSO', [
            'fecha_salida' => now()->toDateString(),
            'hora_retorno_programada' => now()->addHours(3)->format('H:i:s'),
        ]);
        $this->marcarSalida($papeleta);

        $this->artisan('papeletas:marcar-vencidas')->assertSuccessful();

        $papeleta->refresh();
        $this->assertSame('EN_CURSO', $papeleta->estado->codigo);
    }

    public function test_papeleta_en_curso_sin_hora_retorno_usa_max_horas_del_motivo(): void
    {
        $this->motivo->update(['max_horas' => 2]);

        $papeleta = $this->crearPapeleta('EN_CURSO', [
            'fecha_salida' => now()->toDateString(),
            'hora_retorno_programada' => null,
        ]);
        $marcacion = $this->marcarSalida($papeleta);
        Marcacion::whereKey($marcacion->id)->update(['created_at' => now()->subHours(3)]);

        $this->artisan('papeletas:marcar-vencidas')->assertSuccessful();

        $papeleta->refresh();
        $this->assertSame('VENCIDA', $papeleta->estado->codigo);
    }

    public function test_papeleta_en_curso_sin_hora_ni_max_horas_vence_recien_a_medianoche_del_dia_de_salida(): void
    {
        $papeleta = $this->crearPapeleta('EN_CURSO', [
            'fecha_salida' => now()->toDateString(),
            'hora_retorno_programada' => null,
        ]);
        $this->marcarSalida($papeleta);

        $this->artisan('papeletas:marcar-vencidas')->assertSuccessful();

        $papeleta->refresh();
        $this->assertSame(
            'EN_CURSO',
            $papeleta->estado->codigo,
            'No debería vencer todavía: el límite por defecto es 23:59:59 del día de salida (hoy).'
        );
    }

    public function test_papeleta_en_curso_sin_marcacion_de_salida_no_revienta_y_no_se_toca(): void
    {
        // Estado defensivo: en el flujo normal EN_CURSO implica que sí hubo
        // marcación de salida, pero el comando no debe asumirlo ni fallar
        // si por algún motivo no la hay.
        $papeleta = $this->crearPapeleta('EN_CURSO', [
            'fecha_salida' => now()->subDay()->toDateString(),
        ]);

        $this->artisan('papeletas:marcar-vencidas')->assertSuccessful();

        $papeleta->refresh();
        $this->assertSame('EN_CURSO', $papeleta->estado->codigo);
    }

    public function test_papeletas_en_otros_estados_no_se_tocan(): void
    {
        $aprobada = $this->crearPapeleta('APROBADO_RRHH', [
            'fecha_salida' => now()->subDays(5)->toDateString(),
        ]);

        $this->artisan('papeletas:marcar-vencidas')->assertSuccessful();

        $aprobada->refresh();
        $this->assertSame('APROBADO_RRHH', $aprobada->estado->codigo);
    }
}
