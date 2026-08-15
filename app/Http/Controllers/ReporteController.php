<?php

namespace App\Http\Controllers;

use App\Models\Papeleta;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Reportes de salidas: ranking de trabajadores, motivos más usados y horas
 * reales acumuladas por motivo. RRHH ve todo (global); Jefe ve exactamente
 * lo mismo pero acotado a su propio equipo (scopeDeSuEquipo) — misma
 * pantalla, mismo cálculo, distinto alcance según rol.
 */
class ReporteController extends Controller
{
    private const TOP_TRABAJADORES = 10;

    private const TOP_MOTIVOS = 10;

    public function index(Request $request): View
    {
        $usuario = $request->user();

        $desde = $request->date('desde') ?? now()->startOfMonth();
        $hasta = $request->date('hasta') ?? now();

        $base = Papeleta::query()->whereBetween('fecha_salida', [$desde->toDateString(), $hasta->toDateString()]);

        // Único punto donde se decide el alcance: Jefe (y no RRHH/Admin) =
        // solo su equipo. RRHH y Admin ven todo. Un usuario podría en teoría
        // tener varios roles a la vez; RRHH/Admin siempre gana el alcance
        // global si está presente.
        if ($usuario->esJefe() && ! $usuario->esRrhh() && ! $usuario->esAdmin()) {
            $base->deSuEquipo($usuario->id);
        }

        $papeletas = (clone $base)
            ->with(['trabajador:id,name', 'motivo:id,nombre', 'marcaciones:id,papeleta_id,tipo,created_at'])
            ->get();

        return view('reportes.index', [
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'totalSalidas' => $papeletas->count(),
            'rankingTrabajadores' => $this->rankingTrabajadores($papeletas),
            'motivosMasUsados' => $this->motivosMasUsados($papeletas),
            'horasPorMotivo' => $this->horasPorMotivo($papeletas),
        ]);
    }

    /**
     * Trabajadores con mayor cantidad de salidas en el rango, de mayor a menor.
     */
    private function rankingTrabajadores(Collection $papeletas): Collection
    {
        return $papeletas
            ->groupBy('trabajador_id')
            ->map(fn (Collection $grupo) => [
                'nombre' => $grupo->first()->trabajador?->name ?? 'Sin nombre',
                'total' => $grupo->count(),
            ])
            ->sortByDesc('total')
            ->take(self::TOP_TRABAJADORES)
            ->values();
    }

    /**
     * Motivos más usados en el rango, de mayor a menor.
     */
    private function motivosMasUsados(Collection $papeletas): Collection
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
    private function horasPorMotivo(Collection $papeletas): Collection
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

                    return $salida->created_at->diffInHours($retorno->created_at);
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
