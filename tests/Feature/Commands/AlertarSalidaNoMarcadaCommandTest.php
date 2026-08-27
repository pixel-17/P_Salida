<?php

namespace Tests\Feature\Commands;

use App\Notifications\PapeletaSalidaNoMarcadaNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Actions\PapeletaActionTestCase;

class AlertarSalidaNoMarcadaCommandTest extends PapeletaActionTestCase
{
    public function test_alerta_cuando_pasaron_30_minutos_de_la_hora_de_salida_sin_marcar(): void
    {
        Notification::fake();

        $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
            'fecha_salida' => now()->toDateString(),
            'hora_salida_programada' => now()->subMinutes(45)->format('H:i:s'),
        ]);

        $this->artisan('papeletas:alertar-salida-no-marcada')->assertSuccessful();

        $papeleta->refresh();
        $this->assertNotNull($papeleta->alerta_salida_enviada_at);
        $this->assertSame(
            'APROBADO_RRHH',
            $papeleta->estado->codigo,
            'Esta alerta no debe cerrar la papeleta, solo avisar.'
        );
        $this->assertDatabaseHas('historial_papeletas', [
            'papeleta_id' => $papeleta->id,
            'accion' => 'ALERTA_SALIDA_NO_MARCADA',
        ]);
        Notification::assertSentTo($this->trabajador, PapeletaSalidaNoMarcadaNotification::class);
        Notification::assertSentTo($this->jefe, PapeletaSalidaNoMarcadaNotification::class);
    }

    public function test_no_alerta_antes_de_cumplirse_los_30_minutos_de_gracia(): void
    {
        Notification::fake();

        $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
            'fecha_salida' => now()->toDateString(),
            'hora_salida_programada' => now()->subMinutes(10)->format('H:i:s'),
        ]);

        $this->artisan('papeletas:alertar-salida-no-marcada')->assertSuccessful();

        $papeleta->refresh();
        $this->assertNull($papeleta->alerta_salida_enviada_at);
        Notification::assertNothingSent();
    }

    public function test_no_vuelve_a_alertar_una_papeleta_ya_alertada(): void
    {
        Notification::fake();

        $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
            'fecha_salida' => now()->toDateString(),
            'hora_salida_programada' => now()->subHours(2)->format('H:i:s'),
        ]);
        // alerta_salida_enviada_at no es mass-assignable a propósito (ver
        // Papeleta::$fillable); el comando mismo la setea con forceFill().
        $papeleta->forceFill(['alerta_salida_enviada_at' => now()->subHour()])->save();

        $this->artisan('papeletas:alertar-salida-no-marcada')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
