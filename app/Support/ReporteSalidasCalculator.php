<?php

namespace App\Support;

use App\Models\Papeleta;
use Illuminate\Support\Collection;

/**
 * Cálculos de rankings y agregados usados por ReporteController: cuánto
 * pidió cada trabajador, qué área/motivo se repite más, cuánto tiempo pasan
 * fuera. Se centralizan acá (mismo criterio que PapeletaEstadisticas) para
 * que ReporteController se quede solo con las responsabilidades HTTP
 * (resolver el rango/alcance, armar la vista, generar el Excel) y esta
 * lógica de agregación quede testeable aparte, sin pasar por una request.
 *
 * Todos los métodos reciben la misma Collection<Papeleta> ya cargada por
 * ReporteController::papeletasDelRango() — nada de aquí toca la base de
 * datos directamente.
 */
class ReporteSalidasCalculator
{
    private const TOP_TRABAJADORES = 10;

    private const TOP_AREAS = 10;

    private const TOP_MOTIVOS = 10;

    private const TOP_HORAS_FUERA = 10;

    private const TOP_CUADRO_TRABAJADORES = 50;

    /**
     * Trabajadores con mayor cantidad de salidas en el rango, de mayor a
     * menor. Incluye área y jefe directo (tomados de la papeleta más
     * reciente del grupo) para responder "quién solicitó más y quién es
     * su jefe" sin una consulta aparte.
     */
    public static function rankingTrabajadores(Collection $papeletas): Collection
    {
        return $papeletas
            ->groupBy('trabajador_id')
            ->map(fn (Collection $grupo) => [
                'nombre' => $grupo->first()->trabajador?->name ?? 'Sin nombre',
                'area' => $grupo->first()->area?->nombre ?? '—',
                'jefe' => $grupo->first()->jefe?->name ?? '—',
                'total' => $grupo->count(),
            ])
            ->sortByDesc('total')
            ->take(self::TOP_TRABAJADORES)
            ->values();
    }

    /**
     * Cuadro dinámico principal de la pantalla: por cada trabajador junta
     * en una sola fila lo que antes vivía repartido en varios rankings —
     * total de salidas, motivo más recurrente (con cuántas veces lo usó y
     * qué % de sus salidas representa) y horas fuera acumuladas — para que
     * la tabla se pueda buscar/ordenar en el cliente sin volver a pedir
     * datos al servidor. RRHH lo ve global (todos los trabajadores); Jefe
     * ya llega acotado a su equipo porque $papeletas viene filtrado por
     * scopeDeSuEquipo desde papeletasDelRango().
     */
    public static function cuadroTrabajadores(Collection $papeletas): Collection
    {
        return $papeletas
            ->groupBy('trabajador_id')
            ->map(function (Collection $grupo) {
                $motivoTop = $grupo
                    ->groupBy(fn (Papeleta $p) => $p->motivo?->nombre ?? 'Sin motivo')
                    ->map->count()
                    ->sortDesc();

                $segundosFuera = $grupo->sum(function (Papeleta $papeleta) {
                    $salida = $papeleta->marcaciones->firstWhere('tipo', 'SALIDA');
                    $fin = $papeleta->finEfectivoParaHoras();

                    if (! $salida || ! $fin) {
                        return 0;
                    }

                    // $absolute=true: ver nota en Papeleta::horasFuera() —
                    // Carbon 3 devuelve diferencia con signo por defecto y
                    // "horas fuera" nunca debe ser negativo.
                    return $salida->created_at->diffInSeconds($fin, true);
                });

                $total = $grupo->count();
                $motivoTopTotal = $motivoTop->first() ?? 0;

                return [
                    'nombre' => $grupo->first()->trabajador?->name ?? 'Sin nombre',
                    'area' => $grupo->first()->area?->nombre ?? '—',
                    'jefe' => $grupo->first()->jefe?->name ?? '—',
                    'total' => $total,
                    'motivo_top' => $motivoTop->keys()->first() ?? '—',
                    'motivo_top_total' => $motivoTopTotal,
                    'motivo_top_pct' => $total > 0 ? (int) round($motivoTopTotal / $total * 100) : 0,
                    'horas_fuera' => round($segundosFuera / 3600, 1),
                    'ultima_salida' => $grupo->max('fecha_salida')?->format('d/m/Y'),
                ];
            })
            ->sortByDesc('total')
            ->take(self::TOP_CUADRO_TRABAJADORES)
            ->values();
    }

    /**
     * Áreas con más salidas solicitadas en el rango, de mayor a menor.
     */
    public static function rankingAreas(Collection $papeletas): Collection
    {
        return $papeletas
            ->groupBy('area_id')
            ->map(fn (Collection $grupo) => [
                'nombre' => $grupo->first()->area?->nombre ?? 'Sin área',
                'total' => $grupo->count(),
            ])
            ->sortByDesc('total')
            ->take(self::TOP_AREAS)
            ->values();
    }

    /**
     * Trabajadores con más horas fuera acumuladas en el rango: para cada
     * papeleta con marcación de SALIDA, se cuenta el tiempo transcurrido
     * hasta la marcación de RETORNO (si ya volvió) o hasta el fin efectivo
     * calculado por Papeleta::finEfectivoParaHoras() (si sigue afuera —
     * EN_CURSO cuenta en tiempo real hasta ahora; VENCIDA sin retorno topa
     * en el corte de garita del día, no crece para siempre). Esto refleja
     * el tiempo real fuera de la empresa sin que una papeleta vieja sin
     * retorno infle el ranking con cientos de horas — a diferencia de
     * horasPorMotivo, que solo cuenta salidas ya finalizadas.
     */
    public static function rankingHorasFuera(Collection $papeletas): Collection
    {
        return $papeletas
            ->filter(fn (Papeleta $p) => $p->marcaciones->firstWhere('tipo', 'SALIDA') !== null)
            ->groupBy('trabajador_id')
            ->map(function (Collection $grupo) {
                $segundos = $grupo->sum(function (Papeleta $papeleta) {
                    $salida = $papeleta->marcaciones->firstWhere('tipo', 'SALIDA');
                    $fin = $papeleta->finEfectivoParaHoras();

                    // $absolute=true: ver nota en Papeleta::horasFuera().
                    return $fin ? $salida->created_at->diffInSeconds($fin, true) : 0;
                });

                return [
                    'nombre' => $grupo->first()->trabajador?->name ?? 'Sin nombre',
                    'area' => $grupo->first()->area?->nombre ?? '—',
                    'jefe' => $grupo->first()->jefe?->name ?? '—',
                    'horas' => round($segundos / 3600, 1),
                ];
            })
            ->sortByDesc('horas')
            ->take(self::TOP_HORAS_FUERA)
            ->values();
    }

    /**
     * Motivos más usados en el rango, de mayor a menor.
     */
    public static function motivosMasUsados(Collection $papeletas): Collection
    {
        return $papeletas
            ->groupBy('motivo_id')
            ->map(fn (Collection $grupo) => [
                'nombre' => $grupo->first()->motivo?->nombre ?? 'Sin motivo',
                'total' => $grupo->count(),
            ])
            ->sortByDesc('total')
            ->take(self::TOP_MOTIVOS)
            ->values();
    }

    /**
     * Horas reales acumuladas por motivo: diferencia entre la marcación de
     * RETORNO y la de SALIDA (ambas registradas por el vigilante). Solo
     * cuenta papeletas con las dos marcaciones — lo demás no tiene hora de
     * retorno real todavía, no se "contabiliza" a estimar.
     *
     * Calculado en PHP (no con funciones de fecha en SQL) por el mismo
     * motivo que PapeletaEstadisticas::tiempoPromedioAprobacion: el
     * proyecto corre sobre SQLite y MySQL indistintamente.
     */
    public static function horasPorMotivo(Collection $papeletas): Collection
    {
        return $papeletas
            ->filter(function (Papeleta $papeleta) {
                return $papeleta->marcaciones->firstWhere('tipo', 'SALIDA')
                    && $papeleta->marcaciones->firstWhere('tipo', 'RETORNO');
            })
            ->groupBy('motivo_id')
            ->map(function (Collection $grupo) {
                $horas = $grupo->sum(function (Papeleta $papeleta) {
                    $salida = $papeleta->marcaciones->firstWhere('tipo', 'SALIDA');
                    $retorno = $papeleta->marcaciones->firstWhere('tipo', 'RETORNO');

                    // $absolute=true: ver nota en Papeleta::horasFuera().
                    return $salida->created_at->diffInHours($retorno->created_at, true);
                });

                return [
                    'nombre' => $grupo->first()->motivo?->nombre ?? 'Sin motivo',
                    'salidas_contabilizadas' => $grupo->count(),
                    'horas' => round($horas, 1),
                ];
            })
            ->sortByDesc('horas')
            ->values();
    }
}
