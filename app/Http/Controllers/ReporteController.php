<?php

namespace App\Http\Controllers;

use App\Models\Papeleta;
use App\Support\ReporteExcelExporter;
use App\Support\ReporteSalidasCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reportes de salidas: ranking de trabajadores, áreas, motivos, horas
 * fuera y detalle tabulado de cada salida. RRHH ve todo (global); Jefe ve
 * exactamente lo mismo pero acotado a su propio equipo (scopeDeSuEquipo)
 * — misma pantalla, mismo cálculo, distinto alcance según rol.
 *
 * Filtro único: rango de fechas + buscar/estado/área (barra superior de la
 * vista) controlan a la vez los rankings, los gráficos y la tabla de
 * detalle — y el Excel exportado respeta exactamente lo mismo que se ve
 * en pantalla en ese momento.
 *
 * Los rankings/agregados en sí viven en ReporteSalidasCalculator y la
 * escritura del Excel en ReporteExcelExporter — este controlador solo
 * orquesta: resuelve rango/alcance/filtros, arma la vista y arma la
 * respuesta de descarga.
 */
class ReporteController extends Controller
{
    private const POR_PAGINA_DETALLE = 20;

    /**
     * Mismo criterio que PapeletaController::MAX_FILAS_EXPORTAR: sin este
     * tope, papeletasDelRango() carga con ->get() TODO lo que caiga en el
     * rango pedido para calcular los rankings en PHP — sin límite, un
     * rango de fechas amplio (ej. "todo el año") puede significar decenas
     * de miles de filas en memoria en una sola request. Se corta acá,
     * antes de cargar nada, y se le pide al usuario acotar el rango.
     */
    private const MAX_FILAS_REPORTE = 10000;

    public function index(Request $request): View
    {
        ['papeletas' => $papeletas, 'desde' => $desde, 'hasta' => $hasta, 'esSoloJefe' => $esSoloJefe, 'query' => $query]
            = $this->papeletasDelRango($request);

        // ---------- Tabla de detalle: consulta paginada aparte (no sobre
        // la Collection ya cargada en memoria), pero reutiliza la MISMA
        // query base (rango + alcance + filtros de la barra unificada) que
        // ya armó papeletasDelRango(), así que un solo formulario de
        // filtros controla tanto los rankings/gráficos como el detalle. ----------
        $filtrosDetalle = $request->only(['buscar', 'estado_id', 'area_id']);
        $detalleSalidas = (clone $query)
            ->with([
                'trabajador:id,name', 'jefe:id,name', 'area:id,nombre', 'sede:id,nombre', 'motivo:id,nombre',
                'estado:id,codigo,nombre,color', 'marcaciones:id,papeleta_id,tipo,created_at',
            ])
            ->latest('fecha_salida')
            ->paginate(self::POR_PAGINA_DETALLE)
            ->withQueryString();

        return view('reportes.index', [
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'filtrosDetalle' => $filtrosDetalle,
            'estados' => \App\Models\Estado::orderBy('orden')->get(),
            'areas' => \App\Models\Area::orderBy('nombre')->get(),
            'totalSalidas' => $papeletas->count(),
            'esSoloJefe' => $esSoloJefe,
            'rankingTrabajadores' => ReporteSalidasCalculator::rankingTrabajadores($papeletas),
            'rankingAreas' => ReporteSalidasCalculator::rankingAreas($papeletas),
            'rankingHorasFuera' => ReporteSalidasCalculator::rankingHorasFuera($papeletas),
            'motivosMasUsados' => ReporteSalidasCalculator::motivosMasUsados($papeletas),
            'horasPorMotivo' => ReporteSalidasCalculator::horasPorMotivo($papeletas),
            'cuadroTrabajadores' => ReporteSalidasCalculator::cuadroTrabajadores($papeletas),
            'motivoGeneralTop' => ReporteSalidasCalculator::motivosMasUsados($papeletas)->first(),
            'detalleSalidas' => $detalleSalidas,
        ]);
    }

    /**
     * Excel con los 3 rankings de la pantalla de reportes (trabajador que
     * más solicitó, área más solicitada, trabajador con más horas fuera),
     * cada uno en su propia hoja. Mismo rango de fechas y mismo alcance por
     * rol (RRHH ve todo, Jefe solo su equipo) que la pantalla /reportes.
     *
     * "Horas fuera" se calcula igual que en la pantalla y en el Excel de
     * papeletas: desde que el vigilante marca la SALIDA en garita hasta
     * que marca el RETORNO (o hasta ahora, si todavía no ha vuelto).
     */
    public function exportar(Request $request): StreamedResponse
    {
        ['papeletas' => $papeletas, 'desde' => $desde, 'hasta' => $hasta] = $this->papeletasDelRango($request);

        $spreadsheet = new Spreadsheet;
        $subtitulo = 'Del '.$desde->format('d/m/Y').' al '.$hasta->format('d/m/Y');

        ReporteExcelExporter::hojaRanking(
            $spreadsheet,
            'Trabajadores',
            ['Trabajador', 'Área', 'Jefe', 'Total salidas'],
            ReporteSalidasCalculator::rankingTrabajadores($papeletas),
            fn (array $fila) => [$fila['nombre'], $fila['area'], $fila['jefe'], $fila['total']],
            primeraHoja: true,
            subtitulo: $subtitulo,
        );

        ReporteExcelExporter::hojaRanking(
            $spreadsheet,
            'Áreas',
            ['Área', 'Total salidas'],
            ReporteSalidasCalculator::rankingAreas($papeletas),
            fn (array $fila) => [$fila['nombre'], $fila['total']],
            subtitulo: $subtitulo,
        );

        ReporteExcelExporter::hojaRanking(
            $spreadsheet,
            'Horas fuera',
            ['Trabajador', 'Área', 'Jefe', 'Horas fuera (garita: salida → retorno)'],
            ReporteSalidasCalculator::rankingHorasFuera($papeletas),
            fn (array $fila) => [$fila['nombre'], $fila['area'], $fila['jefe'], $fila['horas']],
            subtitulo: $subtitulo,
        );

        ReporteExcelExporter::hojaRanking(
            $spreadsheet,
            'Motivo por trabajador',
            ['Trabajador', 'Área', 'Jefe', 'Total salidas', 'Motivo más recurrente', 'Veces', '% del total', 'Horas fuera', 'Última salida'],
            ReporteSalidasCalculator::cuadroTrabajadores($papeletas),
            fn (array $fila) => [
                $fila['nombre'], $fila['area'], $fila['jefe'], $fila['total'],
                $fila['motivo_top'], $fila['motivo_top_total'], $fila['motivo_top_pct'].'%',
                $fila['horas_fuera'], $fila['ultima_salida'],
            ],
            subtitulo: $subtitulo,
        );

        $spreadsheet->setActiveSheetIndex(0);

        $nombreArchivo = 'reportes_salidas_'.$desde->toDateString().'_a_'.$hasta->toDateString().'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Carga base compartida por index() y exportar(): resuelve el rango de
     * fechas, el alcance según rol (RRHH/Admin = global, Jefe = solo su
     * equipo vía scopeDeSuEquipo), aplica los filtros de la barra unificada
     * (buscar/estado/área vía scopeConFiltros) y trae las papeletas
     * resultantes con las relaciones necesarias para calcular los rankings.
     *
     * También devuelve la query base ya armada (sin ->get()) para que
     * index() pueda paginar el detalle sobre exactamente el mismo filtro,
     * y para que exportar() genere el Excel respetando lo que el usuario
     * esté viendo en pantalla — antes el Excel solo respetaba el rango de
     * fechas e ignoraba buscar/estado/área.
     */
    private function papeletasDelRango(Request $request): array
    {
        $usuario = $request->user();

        $desde = $request->date('desde') ?? now()->startOfMonth();
        $hasta = $request->date('hasta') ?? now();

        $filtros = $request->only(['buscar', 'estado_id', 'area_id']);

        $base = Papeleta::query()
            ->whereBetween('fecha_salida', [$desde->toDateString(), $hasta->toDateString()])
            ->conFiltros($filtros);

        // Único punto donde se decide el alcance: Jefe (y no RRHH/Admin) =
        // solo su equipo. RRHH y Admin ven todo. Un usuario podría en teoría
        // tener varios roles a la vez; RRHH/Admin siempre gana el alcance
        // global si está presente.
        $esSoloJefe = $usuario->esJefe() && ! $usuario->esRrhh() && ! $usuario->esAdmin();
        if ($esSoloJefe) {
            $base->deSuEquipo($usuario->id);
        }

        $total = (clone $base)->count();

        if ($total > self::MAX_FILAS_REPORTE) {
            throw ValidationException::withMessages([
                'reporte' => 'Hay '.number_format($total)." papeletas en ese rango; el máximo para calcular el reporte es "
                    .number_format(self::MAX_FILAS_REPORTE).'. Acota el rango de fechas o el área e inténtalo de nuevo.',
            ]);
        }

        $papeletas = (clone $base)
            ->with([
                'trabajador:id,name',
                'jefe:id,name',
                'area:id,nombre',
                'motivo:id,nombre',
                'marcaciones:id,papeleta_id,tipo,created_at',
            ])
            ->get();

        return [
            'usuario' => $usuario,
            'desde' => $desde,
            'hasta' => $hasta,
            'esSoloJefe' => $esSoloJefe,
            'papeletas' => $papeletas,
            'query' => $base,
        ];
    }
}
