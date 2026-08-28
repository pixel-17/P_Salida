<?php

namespace Tests\Feature;

use App\Enums\TipoMarcacion;
use App\Models\Configuracion;
use App\Models\Marcacion;
use Tests\Feature\Actions\PapeletaActionTestCase;

/**
 * Cubre el bug reportado en producción: "Horas fuera" salía en negativo
 * (ej. -75,1) para papeletas VENCIDA sin retorno.
 *
 * Causa: desde Carbon 3, diffInMinutes()/diffInSeconds()/diffInHours()
 * devuelven la diferencia CON signo por defecto (antes de Carbon 3 el
 * default era siempre positivo). Papeleta::horasFuera() y los cálculos
 * equivalentes en ReporteController/PapeletaEstadisticas no pasaban
 * $absolute=true, así que cuando la marcación real de SALIDA quedaba
 * después del cierre de garita usado como tope (ver
 * Papeleta::finEfectivoParaHoras()) — por ejemplo, una marcación real
 * varios días después de la fecha_salida autorizada — el resultado daba
 * negativo en vez de la magnitud real transcurrida.
 */
class PapeletaHorasFueraTest extends PapeletaActionTestCase
{
    public function test_horas_fuera_nunca_es_negativo_cuando_la_marcacion_real_queda_tras_el_cierre_de_garita(): void
    {
        // Límite de garita = horario_laboral_fin (19:00) + 10 min = 19:10.
        Configuracion::actualizar('horario_laboral_fin', '19:00');

        $papeleta = $this->crearPapeleta('VENCIDA', [
            'fecha_salida' => now()->subDays(3)->toDateString(),
        ]);

        $marcacion = $this->marcarSalida($papeleta);

        // Caso real reportado: la marcación de SALIDA en garita quedó
        // registrada varios días después de la fecha_salida autorizada
        // (después del cierre de garita de ese día, que es el tope que
        // usa finEfectivoParaHoras() para "sin retorno").
        Marcacion::where('id', $marcacion->id)->update([
            'created_at' => $papeleta->fecha_salida->copy()->addDays(3)->setTime(19, 43),
        ]);

        $papeleta->refresh()->load('marcaciones');

        $horasFuera = $papeleta->horasFuera();

        $this->assertNotNull($horasFuera);
        $this->assertGreaterThanOrEqual(0, $horasFuera, 'Horas fuera nunca debe ser negativo.');
    }

    public function test_horas_fuera_calcula_correctamente_con_retorno_marcado(): void
    {
        $papeleta = $this->crearPapeleta('FINALIZADO', [
            'fecha_salida' => now()->toDateString(),
        ]);

        $salida = $this->marcarSalida($papeleta);
        Marcacion::where('id', $salida->id)->update([
            'created_at' => $papeleta->fecha_salida->copy()->setTime(9, 0),
        ]);

        $retorno = Marcacion::create([
            'papeleta_id' => $papeleta->id,
            'tipo' => TipoMarcacion::RETORNO->value,
            'registrado_por_user_id' => $this->vigilante->id,
        ]);
        Marcacion::where('id', $retorno->id)->update([
            'created_at' => $papeleta->fecha_salida->copy()->setTime(13, 0),
        ]);

        $papeleta->refresh()->load('marcaciones');

        $this->assertSame(4.0, $papeleta->horasFuera());
    }
}
