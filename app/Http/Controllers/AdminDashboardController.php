<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPapeleta;
use App\Models\Area;
use App\Models\Papeleta;
use App\Models\Sede;
use App\Models\User;
use App\Support\PapeletaEstadisticas;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Ventana para la serie diaria del gráfico de tendencia. 14 días da
     * una lectura útil de "cómo viene la semana vs la anterior" sin
     * saturar el eje X en el gráfico.
     */
    private const DIAS_TENDENCIA = 14;

    public function __invoke(): View
    {
        $desdeMes = now()->startOfMonth();

        $papeletasDelMes = Papeleta::query()
            ->where('fecha_salida', '>=', $desdeMes)
            ->with('estado:id,codigo')
            ->get();

        $estadosTerminalesNegativos = [
            EstadoPapeleta::RECHAZADO->value,
            EstadoPapeleta::VENCIDA->value,
            EstadoPapeleta::CANCELADO->value,
        ];

        $totalMes = $papeletasDelMes->count();
        $rechazadasVencidasMes = $papeletasDelMes->whereIn('estado.codigo', $estadosTerminalesNegativos)->count();

        return view('admin.dashboard', [
            'totalUsuarios' => User::count(),
            'totalAreas' => Area::count(),
            'totalSedes' => Sede::count(),

            'papeletasPorEstado' => Papeleta::query()
                ->join('estados', 'estados.id', '=', 'papeletas.estado_id')
                ->selectRaw('estados.nombre, estados.color, count(*) as total')
                ->groupBy('estados.nombre', 'estados.color')
                ->orderByDesc('total')
                ->get(),

            'kpis' => [
                'total_mes' => $totalMes,
                'tasa_rechazo_mes' => $totalMes > 0
                    ? round(($rechazadasVencidasMes / $totalMes) * 100, 1)
                    : 0,
                'tiempo_promedio_aprobacion' => PapeletaEstadisticas::tiempoPromedioAprobacion(
                    Papeleta::where('fecha_salida', '>=', $desdeMes)->pluck('id')
                ),
            ],

            'topAreas' => Papeleta::query()
                ->join('areas', 'areas.id', '=', 'papeletas.area_id')
                ->selectRaw('areas.nombre, count(*) as total')
                ->groupBy('areas.nombre')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),

            'topMotivos' => Papeleta::query()
                ->join('motivos', 'motivos.id', '=', 'papeletas.motivo_id')
                ->selectRaw('motivos.nombre, count(*) as total')
                ->groupBy('motivos.nombre')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),

            'tendenciaDiaria' => $this->tendenciaDiaria(),
        ]);
    }

    /**
     * Serie de los últimos N días con el conteo de papeletas creadas cada
     * día (por created_at, no fecha_salida: refleja cuándo se solicitó,
     * que es lo que le interesa a un admin viendo "carga de trabajo").
     * Se rellenan con 0 los días sin registros para que el gráfico no
     * tenga huecos.
     */
    private function tendenciaDiaria(): array
    {
        $desde = now()->subDays(self::DIAS_TENDENCIA - 1)->startOfDay();

        $conteos = Papeleta::query()
            ->where('created_at', '>=', $desde)
            ->selectRaw('DATE(created_at) as dia, count(*) as total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $serie = [];
        for ($i = self::DIAS_TENDENCIA - 1; $i >= 0; $i--) {
            $fecha = now()->subDays($i);
            $clave = $fecha->format('Y-m-d');

            $serie[] = [
                'etiqueta' => $fecha->translatedFormat('d M'),
                'total' => (int) ($conteos[$clave] ?? 0),
            ];
        }

        return $serie;
    }
}
