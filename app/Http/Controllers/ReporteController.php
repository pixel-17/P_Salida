<?php

namespace App\Http\Controllers;

use App\Models\Papeleta;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
 */
class ReporteController extends Controller
{
    private const TOP_TRABAJADORES = 10;

    private const TOP_AREAS = 10;

    private const TOP_MOTIVOS = 10;

    private const TOP_HORAS_FUERA = 10;

    private const POR_PAGINA_DETALLE = 20;

    private const TOP_CUADRO_TRABAJADORES = 50;

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
            'rankingTrabajadores' => $this->rankingTrabajadores($papeletas),
            'rankingAreas' => $this->rankingAreas($papeletas),
            'rankingHorasFuera' => $this->rankingHorasFuera($papeletas),
            'motivosMasUsados' => $this->motivosMasUsados($papeletas),
            'horasPorMotivo' => $this->horasPorMotivo($papeletas),
            'cuadroTrabajadores' => $this->cuadroTrabajadores($papeletas),
            'motivoGeneralTop' => $this->motivosMasUsados($papeletas)->first(),
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

        $this->hojaRanking(
            $spreadsheet,
            'Trabajadores',
            ['Trabajador', 'Área', 'Jefe', 'Total salidas'],
            $this->rankingTrabajadores($papeletas),
            fn (array $fila) => [$fila['nombre'], $fila['area'], $fila['jefe'], $fila['total']],
            primeraHoja: true,
            subtitulo: $subtitulo,
        );

        $this->hojaRanking(
            $spreadsheet,
            'Áreas',
            ['Área', 'Total salidas'],
            $this->rankingAreas($papeletas),
            fn (array $fila) => [$fila['nombre'], $fila['total']],
            subtitulo: $subtitulo,
        );

        $this->hojaRanking(
            $spreadsheet,
            'Horas fuera',
            ['Trabajador', 'Área', 'Jefe', 'Horas fuera (garita: salida → retorno)'],
            $this->rankingHorasFuera($papeletas),
            fn (array $fila) => [$fila['nombre'], $fila['area'], $fila['jefe'], $fila['horas']],
            subtitulo: $subtitulo,
        );

        $this->hojaRanking(
            $spreadsheet,
            'Motivo por trabajador',
            ['Trabajador', 'Área', 'Jefe', 'Total salidas', 'Motivo más recurrente', 'Veces', '% del total', 'Horas fuera', 'Última salida'],
            $this->cuadroTrabajadores($papeletas),
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
     * Escribe una hoja de ranking dentro del spreadsheet: fila de título con
     * el rango de fechas (si se pasa $subtitulo), encabezado con color y
     * panel congelado, filas de datos con franjas alternadas y bordes
     * suaves, y autofiltro (flechas desplegables tipo Excel en cada
     * columna, incluida "Trabajador"/nombre) sobre todo el rango — así se
     * puede filtrar u ordenar por cualquier columna sin tocar código.
     * $filaCallback convierte cada elemento de $datos en el arreglo de
     * columnas a escribir, en el mismo orden que $encabezados.
     */
    private function hojaRanking(
        Spreadsheet $spreadsheet,
        string $titulo,
        array $encabezados,
        Collection $datos,
        \Closure $filaCallback,
        bool $primeraHoja = false,
        ?string $subtitulo = null,
    ): void {
        $hoja = $primeraHoja ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
        $hoja->setTitle($titulo);

        $ultimaColumna = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($encabezados));

        // Fila de título con el rango de fechas del reporte, para que el
        // archivo tenga sentido por sí solo aunque se comparta suelto (sin
        // el nombre de archivo, que sí trae la fecha, a la vista).
        $filaEncabezado = 1;
        if ($subtitulo) {
            $hoja->setCellValue('A1', "{$titulo} — {$subtitulo}");
            $hoja->mergeCells("A1:{$ultimaColumna}1");
            $hoja->getRowDimension(1)->setRowHeight(24);
            $hoja->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '111827']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            ]);
            $filaEncabezado = 2;
        }

        $hoja->fromArray($encabezados, null, "A{$filaEncabezado}");
        $rangoEncabezado = "A{$filaEncabezado}:{$ultimaColumna}{$filaEncabezado}";
        $hoja->getRowDimension($filaEncabezado)->setRowHeight(20);
        $hoja->getStyle($rangoEncabezado)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $filaInicioDatos = $filaEncabezado + 1;
        $fila = $filaInicioDatos;
        foreach ($datos as $item) {
            $hoja->fromArray($filaCallback($item), null, "A{$fila}");
            $fila++;
        }
        $filaFinDatos = $fila - 1;

        if ($filaFinDatos >= $filaInicioDatos) {
            $rangoDatos = "A{$filaInicioDatos}:{$ultimaColumna}{$filaFinDatos}";
            $hoja->getStyle($rangoDatos)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);

            // Franjas alternadas (zebra) para que la fila se pueda seguir
            // con la vista en tablas largas.
            for ($f = $filaInicioDatos; $f <= $filaFinDatos; $f++) {
                if (($f - $filaInicioDatos) % 2 === 1) {
                    $hoja->getStyle("A{$f}:{$ultimaColumna}{$f}")
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
                }
            }
        }

        // Autofiltro: agrega las flechas desplegables de Excel en cada
        // encabezado (Trabajador, Área, Jefe, etc.) para filtrar/ordenar
        // sin escribir fórmulas ni tocar la tabla dinámica de origen.
        $hoja->setAutoFilter("A{$filaEncabezado}:{$ultimaColumna}".max($filaFinDatos, $filaEncabezado));

        // Encabezado siempre visible al bajar por filas largas.
        $hoja->freezePane("A{$filaInicioDatos}");

        foreach (range('A', $ultimaColumna) as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }
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

    /**
     * Trabajadores con mayor cantidad de salidas en el rango, de mayor a
     * menor. Incluye área y jefe directo (tomados de la papeleta más
     * reciente del grupo) para responder "quién solicitó más y quién es
     * su jefe" sin una consulta aparte.
     */
    private function rankingTrabajadores(Collection $papeletas): Collection
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
    private function cuadroTrabajadores(Collection $papeletas): Collection
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
    private function rankingAreas(Collection $papeletas): Collection
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
    private function rankingHorasFuera(Collection $papeletas): Collection
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
