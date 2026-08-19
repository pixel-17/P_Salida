<?php

namespace App\Http\Controllers;

use App\Models\Papeleta;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Módulo independiente de sustentos documentales: por cada papeleta cuyo
 * motivo exige documento, responde "¿presentó el sustento? ¿se lo
 * pidieron y respondió? ¿esa respuesta fue aceptada o volvió a
 * observarse?" — ver Papeleta::estadoSustento().
 *
 * Vive fuera de /reportes a propósito: es una pantalla de trabajo propia
 * (RRHH/Jefe revisan expedientes puntuales), no un tab más dentro del
 * dashboard analítico de reportes. Mismo criterio de alcance por rol que
 * ReporteController: RRHH/Admin ven todo, Jefe solo su equipo.
 */
class SustentoController extends Controller
{
    private const POR_PAGINA = 20;

    /**
     * Igual que ReporteController::MAX_FILAS_REPORTE: tope defensivo para
     * no dejar que un rango de fechas amplio dispare una query sin límite.
     */
    private const MAX_FILAS = 10000;

    public function index(Request $request): View
    {
        $usuario = $request->user();

        $desde = $request->date('desde') ?? now()->startOfMonth();
        $hasta = $request->date('hasta') ?? now();

        $filtros = $request->only(['buscar', 'estado_sustento']);

        $base = Papeleta::query()
            ->whereBetween('fecha_salida', [$desde->toDateString(), $hasta->toDateString()])
            ->whereHas('motivo', fn ($q) => $q->where('requiere_documento', true))
            ->conFiltros($request->only(['buscar']));

        $esSoloJefe = $usuario->esJefe() && ! $usuario->esRrhh() && ! $usuario->esAdmin();
        if ($esSoloJefe) {
            $base->deSuEquipo($usuario->id);
        }

        if ((clone $base)->count() > self::MAX_FILAS) {
            throw ValidationException::withMessages([
                'sustentos' => 'Hay demasiadas papeletas en ese rango. Acota el rango de fechas e inténtalo de nuevo.',
            ]);
        }

        $consultaBase = (clone $base)->with([
            'trabajador:id,name', 'area:id,nombre', 'motivo:id,nombre,requiere_documento',
            'estado:id,codigo,nombre,color',
            'adjuntos' => fn ($q) => $q->latest(),
            'observacionesJustificacion.usuario:id,name',
            'observacionesJustificacion.adjuntos' => fn ($q) => $q->latest(),
        ])->latest('fecha_salida');

        // "estado_sustento" es un valor derivado (Papeleta::estadoSustento()),
        // no una columna: no se puede filtrar/paginar en SQL. Se trae todo
        // el universo del rango (acotado por MAX_FILAS arriba), se filtra
        // en PHP y se pagina manualmente sobre esa colección ya filtrada
        // — así el total y los links de paginación quedan correctos.
        // Sin ese filtro, se pagina en BD como de costumbre (más liviano).
        if ($filtros['estado_sustento'] ?? null) {
            $filtradas = $consultaBase->get()
                ->filter(fn (Papeleta $p) => $p->estadoSustento()['codigo'] === $filtros['estado_sustento'])
                ->values();

            $pagina = LengthAwarePaginator::resolveCurrentPage();

            $sustentos = new LengthAwarePaginator(
                $filtradas->forPage($pagina, self::POR_PAGINA),
                $filtradas->count(),
                self::POR_PAGINA,
                $pagina,
                ['path' => $request->url(), 'query' => $request->query()],
            );
        } else {
            $sustentos = $consultaBase->paginate(self::POR_PAGINA)->withQueryString();
        }

        return view('sustentos.index', [
            'sustentos' => $sustentos,
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'filtros' => $filtros,
            'esSoloJefe' => $esSoloJefe,
        ]);
    }
}
