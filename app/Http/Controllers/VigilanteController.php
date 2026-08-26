<?php

namespace App\Http\Controllers;

use App\Actions\MarcarRetornoVigilanteAction;
use App\Actions\MarcarSalidaVigilanteAction;
use App\Enums\EstadoPapeleta;
use App\Enums\TipoMarcacion;
use App\Models\Configuracion;
use App\Models\Papeleta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vista mínima para la garita: buscar por código/DNI/nombre (a mano o vía
 * QR escaneado con la cámara) y confirmar salida o retorno. Aplica en todas
 * las sedes; solo el vigilante de la misma sede que la papeleta puede
 * confirmar (ver PapeletaPolicy::marcarComoVigilante). El trabajador nunca
 * marca su propia salida/retorno (ver PapeletaPolicy::marcar).
 *
 * A propósito NO reutiliza PapeletaController@show ni sus vistas: el
 * vigilante no debe ver el detalle completo de la papeleta (motivo,
 * observaciones, adjuntos, historial), solo lo justo para confirmar.
 */
class VigilanteController extends Controller
{
    public function index(): View
    {
        $user = request()->user();

        abort_unless($user->esVigilante(), 403);

        return view('vigilancia.index', [
            'sedeNombre' => $user->sede?->nombre,
            'horaLimiteRegistro' => Configuracion::horaLimiteGarita(),
        ]);
    }

    public function buscar(Request $request): JsonResponse
    {
        abort_unless($request->user()->esVigilante(), 403);

        $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);

        $q = $request->input('q');

        $papeletas = Papeleta::query()
            ->where('sede_id', $request->user()->sede_id)
            ->whereHas('estado', fn ($query) => $query->whereIn('codigo', [
                EstadoPapeleta::APROBADO_RRHH->value,
                EstadoPapeleta::EN_CURSO->value,
            ]))
            ->where(function ($query) use ($q) {
                $query->where('codigo', 'like', "%{$q}%")
                    ->orWhereHas('trabajador', fn ($tq) => $tq
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('dni', 'like', "%{$q}%")
                    );
            })
            ->with(['trabajador:id,name,dni', 'estado:id,codigo,nombre'])
            ->limit(10)
            ->get()
            ->map(fn (Papeleta $p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'trabajador' => $p->trabajador->name,
                'dni' => $p->trabajador->dni,
                'estado' => $p->estado->codigo,
                'fecha_salida' => $p->fecha_salida->format('d/m/Y'),
                'dias_para_salida' => $p->diasParaSalida(),
                // No basta con estar en APROBADO_RRHH: la fecha autorizada
                // tiene que ser exactamente hoy (ver
                // Papeleta::esHoyFechaDeSalida / MarcarSalidaVigilanteAction,
                // que es la barrera real — esto es solo para no ofrecer un
                // botón que el servidor va a rechazar igual).
                'puede_salida' => $p->estado->codigo === EstadoPapeleta::APROBADO_RRHH->value && $p->esHoyFechaDeSalida(),
                'puede_retorno' => $p->estado->codigo === EstadoPapeleta::EN_CURSO->value,
            ]);

        return response()->json(['papeletas' => $papeletas]);
    }

    /**
     * Panel "hoy en mi sede": quién falta por salir, quién está fuera (salió
     * y no ha retornado) y quién ya finalizó su papeleta hoy. Se filtra por
     * fecha_salida = hoy, igual que el resto del flujo de papeletas.
     */
    public function resumen(Request $request): JsonResponse
    {
        abort_unless($request->user()->esVigilante(), 403);

        $papeletas = Papeleta::query()
            ->where('sede_id', $request->user()->sede_id)
            ->whereDate('fecha_salida', now()->toDateString())
            ->whereHas('estado', fn ($query) => $query->whereIn('codigo', [
                EstadoPapeleta::APROBADO_RRHH->value,
                EstadoPapeleta::EN_CURSO->value,
                EstadoPapeleta::FINALIZADO->value,
            ]))
            ->with([
                'trabajador:id,name,dni',
                'estado:id,codigo,nombre',
                'marcaciones' => fn ($q) => $q->with('registradoPor:id,name')->orderBy('created_at'),
            ])
            ->get()
            ->map(fn (Papeleta $p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'trabajador' => $p->trabajador->name,
                'dni' => $p->trabajador->dni,
                'estado' => $p->estado->codigo,
                'hora_salida_programada' => $p->hora_salida_programada,
                'hora_retorno_programada' => $p->hora_retorno_programada,
                'salida' => $this->resumirMarcacion($p, TipoMarcacion::SALIDA),
                'retorno' => $this->resumirMarcacion($p, TipoMarcacion::RETORNO),
            ]);

        return response()->json([
            'pendientes' => $papeletas->where('estado', EstadoPapeleta::APROBADO_RRHH->value)->values(),
            'en_curso' => $papeletas->where('estado', EstadoPapeleta::EN_CURSO->value)->values(),
            'finalizadas' => $papeletas->where('estado', EstadoPapeleta::FINALIZADO->value)->values(),
        ]);
    }

    private function resumirMarcacion(Papeleta $papeleta, TipoMarcacion $tipo): ?array
    {
        $marcacion = $papeleta->marcaciones->firstWhere('tipo', $tipo);

        if (! $marcacion) {
            return null;
        }

        return [
            'hora' => $marcacion->created_at->format('H:i'),
            'registrado_por' => $marcacion->registradoPor?->name,
        ];
    }

    public function confirmarSalida(Papeleta $papeleta, MarcarSalidaVigilanteAction $action): RedirectResponse|JsonResponse
    {
        $action->execute($papeleta, request()->user());

        $mensaje = "Salida confirmada: {$papeleta->codigo}.";

        if (request()->wantsJson()) {
            return response()->json(['mensaje' => $mensaje]);
        }

        return back()->with('status', $mensaje);
    }

    public function confirmarRetorno(Papeleta $papeleta, MarcarRetornoVigilanteAction $action): RedirectResponse|JsonResponse
    {
        $action->execute($papeleta, request()->user());

        $mensaje = "Retorno confirmado: {$papeleta->codigo}. Papeleta finalizada.";

        if (request()->wantsJson()) {
            return response()->json(['mensaje' => $mensaje]);
        }

        return back()->with('status', $mensaje);
    }
}
