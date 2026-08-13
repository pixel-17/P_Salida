<?php

namespace App\Http\Controllers;

use App\Actions\CrearPapeletaAction;
use App\Enums\EstadoPapeleta;
use App\Enums\RolUsuario;
use App\Http\Requests\StorePapeletaRequest;
use App\Models\Area;
use App\Models\Estado;
use App\Models\HistorialPapeleta;
use App\Models\Papeleta;
use App\Support\PapeletaEstadisticas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PapeletaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $filtros = $request->only(['buscar', 'estado_id', 'area_id', 'desde', 'hasta']);
        $vista = $request->get('vista', 'pendientes') === 'todas' ? 'todas' : 'pendientes';

        $papeletas = match (true) {
            $user->hasRole(RolUsuario::JEFE) => ($vista === 'todas'
                    ? Papeleta::deSuEquipo($user->id)
                    : Papeleta::pendientesDeJefe($user->id))
                ->conFiltros($filtros)
                ->with(['trabajador', 'motivo', 'estado'])
                ->latest('fecha_salida')
                ->paginate(15)
                ->withQueryString(),

            $user->hasRole(RolUsuario::RRHH) => ($vista === 'todas'
                    ? Papeleta::query()
                    : Papeleta::pendientesDeRrhh())
                ->conFiltros($filtros)
                ->with(['trabajador', 'jefe', 'motivo', 'estado'])
                ->latest('fecha_salida')
                ->paginate(15)
                ->withQueryString(),

            default => Papeleta::delTrabajador($user->id)
                ->conFiltros($filtros)
                ->with(['motivo', 'estado'])
                ->paginate(15)
                ->withQueryString(),
        };

        // La lista en sí se pide por AJAX (tabs, filtros, chips de fecha y
        // "cargar más" no recargan la página, ver papeletas/index.blade.php).
        // La carga inicial del documento sigue siendo un GET normal para que
        // la URL con filtros sea compartible/recargable.
        if ($request->wantsJson()) {
            return response()->json([
                'papeletas' => collect($papeletas->items())->map(fn (Papeleta $p) => [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'destino' => $p->destino,
                    'fecha_salida' => $p->fecha_salida->format('d/m/Y'),
                    'motivo' => $p->motivo->nombre,
                    'trabajador' => ($user->esJefe() || $user->esRrhh()) ? $p->trabajador->name : null,
                    'estado' => ['nombre' => $p->estado->nombre, 'color' => $p->estado->color],
                    'url' => route('papeletas.show', $p),
                ]),
                'meta' => [
                    'current_page' => $papeletas->currentPage(),
                    'last_page' => $papeletas->lastPage(),
                    'total' => $papeletas->total(),
                    'has_more' => $papeletas->hasMorePages(),
                ],
            ]);
        }

        $areas = ($user->esJefe() || $user->esRrhh())
            ? Area::activas()->orderBy('nombre')->get()
            : collect();

        $estados = Estado::orderBy('orden')->get();

        return view('papeletas.index', compact(
            'papeletas',
            'filtros',
            'areas',
            'estados',
            'vista'
        ));
    }

    private const MAX_FILAS_EXPORTAR = 10000;

    public function exportar(Request $request): StreamedResponse|RedirectResponse
    {
        $filtros = $request->only(['buscar', 'estado_id', 'area_id', 'desde', 'hasta']);
        $vista = $request->get('vista', 'todas') === 'pendientes' ? 'pendientes' : 'todas';

        $base = match (true) {
            $vista === 'pendientes' && $request->user()->hasRole(RolUsuario::RRHH)
                => Papeleta::pendientesDeRrhh(),
            default => Papeleta::query(),
        };

        $query = $base->conFiltros($filtros);
        $total = (clone $query)->count();

        if ($total > self::MAX_FILAS_EXPORTAR) {
            return back()->withErrors([
                'exportar' => 'Hay '.number_format($total)." papeletas con esos filtros; el máximo por descarga es "
                    .number_format(self::MAX_FILAS_EXPORTAR).'. Acota el rango de fechas o el área e inténtalo de nuevo.',
            ]);
        }

        $encabezados = [
            'Código', 'Trabajador', 'Jefe', 'Área', 'Sede', 'Motivo',
            'Destino', 'Fecha salida', 'Hora salida', 'Hora retorno', 'Estado',
        ];

        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Papeletas');
        $hoja->fromArray($encabezados, null, 'A1');
        $hoja->getStyle('A1:K1')->getFont()->setBold(true);

        $papeletas = $query
            ->with(['trabajador', 'jefe', 'area', 'sede', 'motivo', 'estado'])
            ->latest('fecha_salida')
            ->get();

        $fila = 2;
        foreach ($papeletas as $papeleta) {
            $hoja->fromArray([
                $papeleta->codigo,
                $papeleta->trabajador->name,
                $papeleta->jefe?->name,
                $papeleta->area?->nombre,
                $papeleta->sede?->nombre,
                $papeleta->motivo->nombre,
                $papeleta->destino,
                $papeleta->fecha_salida->format('d/m/Y'),
                $papeleta->hora_salida_programada,
                $papeleta->hora_retorno_programada,
                $papeleta->estado->nombre,
            ], null, "A{$fila}");
            $fila++;
        }

        foreach (range('A', 'K') as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        $nombreArchivo = 'papeletas_'.now()->format('Y-m-d_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Boleta individual en PDF: mismo dato que ve el trabajador/jefe/RRHH en
     * pantalla (papeletas.show), pero en formato imprimible con espacio
     * para firmas físicas. Usa la misma Policy que la vista de detalle.
     */
    public function pdfBoleta(Papeleta $papeleta)
    {
        $this->authorize('ver', $papeleta);

        $papeleta->load([
            'trabajador', 'jefe', 'area', 'sede', 'motivo', 'estado',
            'marcaciones.registradoPor', 'historial.usuario',
        ]);

        $pdf = Pdf::loadView('papeletas.pdf.boleta', compact('papeleta'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("{$papeleta->codigo}.pdf");
    }

    /**
     * Reporte gerencial en PDF: mismos filtros y restricción de rol que
     * exportar() (solo RRHH), pero con KPIs de cabecera para lectura rápida
     * en vez de solo la tabla cruda que da el Excel.
     */
    public function pdfReporte(Request $request): mixed
    {
        $filtros = $request->only(['buscar', 'estado_id', 'area_id', 'desde', 'hasta']);
        $vista = $request->get('vista', 'todas') === 'pendientes' ? 'pendientes' : 'todas';

        $base = match (true) {
            $vista === 'pendientes' && $request->user()->hasRole(RolUsuario::RRHH)
                => Papeleta::pendientesDeRrhh(),
            default => Papeleta::query(),
        };

        $query = $base->conFiltros($filtros);
        $total = (clone $query)->count();

        if ($total > self::MAX_FILAS_EXPORTAR) {
            return back()->withErrors([
                'exportar' => 'Hay '.number_format($total).' papeletas con esos filtros para el reporte PDF; el máximo por descarga es '
                    .number_format(self::MAX_FILAS_EXPORTAR).'. Acota el rango de fechas o el área e inténtalo de nuevo.',
            ]);
        }

        $papeletas = $query
            ->with(['trabajador', 'area', 'motivo', 'estado'])
            ->latest('fecha_salida')
            ->get();

        $kpis = [
            'total' => $papeletas->count(),
            'finalizadas' => $papeletas->where('estado.codigo', EstadoPapeleta::FINALIZADO->value)->count(),
            'rechazadas_vencidas' => $papeletas->whereIn('estado.codigo', [
                EstadoPapeleta::RECHAZADO->value,
                EstadoPapeleta::VENCIDA->value,
                EstadoPapeleta::CANCELADO->value,
            ])->count(),
            'tiempo_promedio_aprobacion' => PapeletaEstadisticas::tiempoPromedioAprobacion($papeletas->pluck('id')),
        ];

        $etiquetas = [
            'buscar' => 'Búsqueda',
            'estado_id' => 'Estado',
            'area_id' => 'Área',
            'desde' => 'Desde',
            'hasta' => 'Hasta',
        ];
        $filtrosLegibles = collect($filtros)
            ->filter()
            ->map(fn ($valor, $clave) => ($etiquetas[$clave] ?? $clave).': '.$valor)
            ->values()
            ->all();

        $pdf = Pdf::loadView('papeletas.pdf.reporte', compact('papeletas', 'kpis', 'filtrosLegibles'))
            ->setPaper('a4', 'landscape');

        $nombreArchivo = 'reporte_papeletas_'.now()->format('Y-m-d_His').'.pdf';

        return $pdf->stream($nombreArchivo);
    }

    public function create(): View
    {
        $this->authorize('crear', Papeleta::class);

        return view('papeletas.create');
    }

    public function store(StorePapeletaRequest $request, CrearPapeletaAction $action): RedirectResponse
    {
        $papeleta = $action->execute($request->user(), $request->validated());

        return redirect()
            ->route('papeletas.show', $papeleta)
            ->with('status', "Papeleta {$papeleta->codigo} creada y enviada a tu jefe.");
    }

    public function show(Papeleta $papeleta): View
    {
        $this->authorize('ver', $papeleta);

        $papeleta->load([
            'trabajador', 'jefe', 'area', 'sede', 'motivo', 'estado',
            'marcaciones', 'flujoAprobaciones.usuario', 'observaciones.usuario',
            'adjuntos', 'historial.usuario',
        ]);

        return view('papeletas.show', compact('papeleta'));
    }

    /**
     * Consulta rápida del estado actual.
     *
     * Se reemplaza SSE por una respuesta JSON inmediata para evitar
     * conexiones PHP abiertas durante 50 segundos con php artisan serve.
     * El frontend consulta periódicamente esta ruta.
     */
    public function eventos(Request $request, Papeleta $papeleta): JsonResponse
    {
        $this->authorize('ver', $papeleta);

        $papeleta->loadMissing('estado');

        return response()->json([
            'estado_id' => $papeleta->estado_id,
            'estado' => $papeleta->estado->codigo,
            'updated_at' => $papeleta->updated_at?->toIso8601String(),
        ]);
    }

    public function anular(Request $request, Papeleta $papeleta): RedirectResponse
    {
        $this->authorize('anular', $papeleta);

        $estadoAnterior = $papeleta->estado->codigo;

        $papeleta->update([
            'estado_id' => Estado::porCodigo(EstadoPapeleta::RECHAZADO)->id,
        ]);

        $papeleta->delete();

        HistorialPapeleta::registrar(
            $papeleta,
            $request->user(),
            'ANULADA',
            $estadoAnterior,
            EstadoPapeleta::RECHAZADO->value,
            'Anulada por el propio trabajador'
        );

        return redirect()->route('papeletas.index')->with('status', 'Papeleta anulada.');
    }
}
