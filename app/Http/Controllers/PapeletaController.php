<?php

namespace App\Http\Controllers;

use App\Actions\CancelarPapeletaAction;
use App\Actions\CrearPapeletaAction;
use App\Enums\RolUsuario;
use App\Http\Requests\StorePapeletaRequest;
use App\Models\Area;
use App\Models\Estado;
use App\Models\Papeleta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PapeletaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $filtros = $request->only(['buscar', 'estado_id', 'area_id', 'desde', 'hasta']);
        $vista = $request->get('vista', 'pendientes') === 'todas' ? 'todas' : 'pendientes';

        // Si nadie pidió un rango de fechas explícito, la bandeja por
        // defecto solo trae de hoy en adelante (no el historial completo).
        // Evita cargar/paginar años de papeletas antiguas en cada visita;
        // quien de verdad necesita ver fechas pasadas lo pide a propósito
        // con el filtro "desde" del formulario. $filtros (lo que llega a la
        // vista para pintar chips/inputs) queda intacto para no alterar esa UI.
        $sinFiltroFecha = empty($filtros['desde']) && empty($filtros['hasta']);

        $papeletas = match (true) {
            // Administrador: acceso total sin restricción de bandeja — ve
            // y busca cualquier papeleta sin importar estado, trabajador o
            // jefe asignado. No tiene concepto de "pendientes" (no decide
            // aprobaciones, ver PapeletaPolicy::decidir), así que ignora el
            // toggle vista y siempre trae el universo completo.
            $user->hasRole(RolUsuario::ADMINISTRADOR) => Papeleta::query()
                ->conFiltros($filtros)
                ->when($sinFiltroFecha, fn ($q) => $q->whereDate('fecha_salida', '>=', now()->toDateString()))
                ->with(['trabajador', 'jefe', 'motivo', 'estado'])
                ->latest('fecha_salida')
                ->paginate(15)
                ->withQueryString(),

            $user->hasRole(RolUsuario::JEFE) => ($vista === 'todas'
                    ? Papeleta::deSuEquipo($user->id)
                    : Papeleta::pendientesDeJefe($user->id))
                ->conFiltros($filtros)
                ->when($sinFiltroFecha, fn ($q) => $q->whereDate('fecha_salida', '>=', now()->toDateString()))
                ->with(['trabajador', 'motivo', 'estado'])
                ->latest('fecha_salida')
                ->paginate(15)
                ->withQueryString(),

            $user->hasRole(RolUsuario::RRHH) => ($vista === 'todas'
                    ? Papeleta::query()
                    : Papeleta::pendientesDeRrhh())
                ->conFiltros($filtros)
                ->when($sinFiltroFecha, fn ($q) => $q->whereDate('fecha_salida', '>=', now()->toDateString()))
                ->with(['trabajador', 'jefe', 'motivo', 'estado'])
                ->latest('fecha_salida')
                ->paginate(15)
                ->withQueryString(),

            default => Papeleta::delTrabajador($user->id)
                ->conFiltros($filtros)
                ->when($sinFiltroFecha, fn ($q) => $q->whereDate('fecha_salida', '>=', now()->toDateString()))
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
                    'trabajador' => ($user->esJefe() || $user->esRrhh() || $user->esAdmin()) ? $p->trabajador->name : null,
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

        $areas = ($user->esJefe() || $user->esRrhh() || $user->esAdmin())
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

    /**
     * Boleta individual en PDF: mismo dato que ve el trabajador/jefe/RRHH en
     * pantalla (papeletas.show), pero en formato imprimible con espacio
     * para firmas físicas. Usa la misma Policy que la vista de detalle.
     */
    public function pdfBoleta(Papeleta $papeleta)
    {
        $this->authorize('ver', $papeleta);

        $papeleta->load([
            'trabajador.cargo', 'jefe', 'area', 'sede', 'motivo', 'estado',
            'marcaciones.registradoPor', 'historial.usuario',
        ]);

        $pdf = Pdf::loadView('papeletas.pdf.boleta', compact('papeleta'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("{$papeleta->codigo}.pdf");
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

    /**
     * Cancelación manual por el propio trabajador (con motivo obligatorio).
     * El front pide doble confirmación antes de enviar este POST — ver
     * papeletas/show.blade.php.
     */
    public function cancelar(Request $request, Papeleta $papeleta, CancelarPapeletaAction $accion): RedirectResponse
    {
        $datos = $request->validate([
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'motivo.required' => 'Cuéntanos brevemente por qué cancelas la papeleta.',
            'motivo.min' => 'El motivo es muy corto, dinos un poco más.',
        ]);

        $accion->execute($papeleta, $request->user(), $datos['motivo']);

        return redirect()->route('papeletas.show', $papeleta)->with('status', 'Papeleta cancelada.');
    }
}
