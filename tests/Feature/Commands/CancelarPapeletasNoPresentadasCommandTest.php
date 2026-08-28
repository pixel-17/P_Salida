<?php

namespace Tests\Feature\Commands;

use App\Notifications\PapeletaVencidaNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PapeletaActionTestCase;

/**
 * Cubre `papeletas:cancelar-no-presentadas`. Reproduce directamente el bug
 * reportado en producción: una papeleta APROBADO_RRHH con fecha_salida ya
 * vencida que nunca marcó salida se quedaba mostrando "aprobado" en vez de
 * pasar a VENCIDA. La causa real era que el Schedule no estaba corriendo
 * (falta de `schedule:run` en cron), pero este test existe para que, si el
 * comando en sí se rompe en el futuro (estado mal referenciado, condición
 * de fecha invertida, etc.), CI lo detecte antes que un usuario.
 */
class CancelarPapeletasNoPresentadasCommandTest extends PapeletaActionTestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function estadosEnTramite(): array
    {
        return [
            'SOLICITADO' => ['SOLICITADO'],
            'APROBADO_JEFE' => ['APROBADO_JEFE'],
            'APROBADO_RRHH' => ['APROBADO_RRHH'],
            'OBSERVADO' => ['OBSERVADO'],
        ];
    }

    #[DataProvider('estadosEnTramite')]
    public function test_papeleta_en_tramite_con_fecha_salida_vencida_pasa_a_vencida(string $estadoCodigo): void
    {
        Notification::fake();

        $papeleta = $this->crearPapeleta($estadoCodigo, [
            'fecha_salida' => now()->subDays(2)->toDateString(),
        ]);

        $this->artisan('papeletas:cancelar-no-presentadas')->assertSuccessful();

        $papeleta->refresh();
        $this->assertSame('VENCIDA', $papeleta->estado->codigo);
        $this->assertDatabaseHas('historial_papeletas', [
            'papeleta_id' => $papeleta->id,
            'accion' => 'VENCIDA_AUTOMATICA_NO_PRESENTADO',
        ]);
        Notification::assertSentTo($this->trabajador, PapeletaVencidaNotification::class);
        Notification::assertSentTo($this->jefe, PapeletaVencidaNotification::class);
    }

    public function test_papeleta_con_fecha_salida_de_hoy_tambien_vence_pensado_para_correr_cerca_de_medianoche(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
            'fecha_salida' => now()->toDateString(),
        ]);

        $this->artisan('papeletas:cancelar-no-presentadas')->assertSuccessful();

        $papeleta->refresh();
        $this->assertSame('VENCIDA', $papeleta->estado->codigo);
    }

    public function test_papeleta_con_fecha_salida_futura_no_se_toca(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
            'fecha_salida' => now()->addDays(3)->toDateString(),
        ]);

        $this->artisan('papeletas:cancelar-no-presentadas')->assertSuccessful();

        $papeleta->refresh();
        $this->assertSame('APROBADO_RRHH', $papeleta->estado->codigo);
    }

    public function test_papeleta_en_curso_queda_fuera_a_proposito(): void
    {
        // EN_CURSO ya tiene al trabajador afuera; su cierre por tiempo lo
        // maneja papeletas:marcar-vencidas, no este comando.
        $papeleta = $this->crearPapeleta('EN_CURSO', [
            'fecha_salida' => now()->subDays(2)->toDateString(),
        ]);
        $this->marcarSalida($papeleta);

        $this->artisan('papeletas:cancelar-no-presentadas')->assertSuccessful();

        $papeleta->refresh();
        $this->assertSame('EN_CURSO', $papeleta->estado->codigo);
    }

    public function test_papeletas_ya_terminales_no_se_tocan(): void
    {
        $finalizada = $this->crearPapeleta('FINALIZADO', [
            'fecha_salida' => now()->subDays(10)->toDateString(),
        ]);
        $rechazada = $this->crearPapeleta('RECHAZADO', [
            'fecha_salida' => now()->subDays(10)->toDateString(),
        ]);

        $this->artisan('papeletas:cancelar-no-presentadas')->assertSuccessful();

        $this->assertSame('FINALIZADO', $finalizada->fresh()->estado->codigo);
        $this->assertSame('RECHAZADO', $rechazada->fresh()->estado->codigo);
    }
}
