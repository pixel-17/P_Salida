<?php

namespace App\Http\Controllers;
use App\Models\Atencion;
use App\Models\Estudiante;
use App\Models\Motivo;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index(Request $request) {
        // Atenciones por mes (últimos 6 meses)
        $atencionsPorMes = Atencion::selectRaw('MONTH(fecha) as mes, YEAR(fecha) as anio, COUNT(*) as total')
            ->where('fecha', '>=', now()->subMonths(6))
            ->groupByRaw('YEAR(fecha), MONTH(fecha)')
            ->orderByRaw('YEAR(fecha), MONTH(fecha)')
            ->get();
 
        // Motivos más frecuentes
        $motivosFrecuentes = Motivo::withCount('atenciones')
            ->orderByDesc('atenciones_count')
            ->limit(10)
            ->get();
 
        // Casos recurrentes
        $casosRecurrentes = Atencion::with(['estudiante', 'motivo'])
            ->where('es_recurrente', true)
            ->orderByDesc('fecha')
            ->limit(10)
            ->get();
 
        // Atenciones del mes actual
        $atencionsMes = Atencion::whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->count();
 
        return view('reportes.index', compact(
            'atencionsPorMes', 'motivosFrecuentes',
            'casosRecurrentes', 'atencionsMes'
        ));
    }
}
