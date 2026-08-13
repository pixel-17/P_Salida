<?php

namespace App\Support;

use App\Enums\EstadoPapeleta;
use App\Models\HistorialPapeleta;
use Illuminate\Support\Collection;

/**
 * Cálculos que antes vivían solo dentro de PapeletaController::pdfReporte()
 * y que ahora también usa AdminDashboardController. Se centralizan acá
 * para no duplicar la lógica del promedio de aprobación en dos sitios.
 */
class PapeletaEstadisticas
{
    /**
     * Promedio (texto "Xh Ym") entre la creación de cada papeleta y el
     * momento en que quedó APROBADO_RRHH, a partir del historial de
     * eventos. Solo cuenta las que efectivamente llegaron a ese estado.
     *
     * Calculado en PHP (no con AVG/TIMESTAMPDIFF en SQL) porque el
     * proyecto corre tanto en SQLite (dev/tests) como MySQL (prod), y esas
     * funciones de fecha no son portables entre motores.
     */
    public static function tiempoPromedioAprobacion(Collection $papeletaIds): string
    {
        if ($papeletaIds->isEmpty()) {
            return '—';
        }

        $eventos = HistorialPapeleta::query()
            ->whereIn('papeleta_id', $papeletaIds)
            ->whereHas('estadoNuevo', fn ($q) => $q->where('codigo', EstadoPapeleta::APROBADO_RRHH->value))
            ->with('papeleta:id,created_at')
            ->get(['id', 'papeleta_id', 'created_at']);

        $segundos = $eventos
            ->filter(fn ($evento) => $evento->papeleta !== null)
            ->map(fn ($evento) => $evento->papeleta->created_at->diffInSeconds($evento->created_at));

        if ($segundos->isEmpty()) {
            return '—';
        }

        $segundosPromedio = (int) $segundos->avg();
        $horas = intdiv($segundosPromedio, 3600);
        $minutos = intdiv($segundosPromedio % 3600, 60);

        return $horas > 0 ? "{$horas}h {$minutos}m" : "{$minutos}m";
    }
}
