<?php

namespace App\Http\Controllers;
use App\Models\Atencion;
use App\Models\Estudiante;
use App\Models\Motivo;
use App\Models\CategoriaMotivo;
use App\Services\NotificacionEstudianteService;
use Illuminate\Http\Request;

class AtencionController extends Controller
{
    public function index(Request $request) {
        $query = Atencion::with(['estudiante', 'motivo.categoria', 'enfermera']);
 
        if ($request->filled('dni')) {
            $query->whereHas('estudiante', fn($q) => $q->where('dni', $request->dni));
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }
        if ($request->filled('motivo_id')) {
            $query->where('motivo_id', $request->motivo_id);
        }
        if ($request->filled('recurrente')) {
            $query->where('es_recurrente', true);
        }
 
        $atenciones = $query->orderByDesc('fecha')->paginate(15);
        $motivos     = Motivo::orderBy('nombre')->get();
 
        return view('atenciones.index', compact('atenciones', 'motivos'));
    }
 
    public function create() {
        $categorias = CategoriaMotivo::with('motivos')->get();
        $estudiantes = Estudiante::orderBy('apellidos')->get();
        return view('atenciones.create', compact('categorias', 'estudiantes'));
    }
 
    public function store(Request $request) {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'motivo_id'     => 'required|exists:motivos,id',
            'fecha'         => 'required|date',
            'diagnostico'   => 'nullable|string',
            'tratamiento'   => 'nullable|string',
            'observaciones' => 'nullable|string',
            'derivado'      => 'nullable|boolean',
        ]);
 
        // Detectar recurrencia (mismo motivo >= 3 veces)
        $totalAtenciones = Atencion::where('estudiante_id', $request->estudiante_id)
            ->where('motivo_id', $request->motivo_id)
            ->count();
        $esRecurrente = $totalAtenciones >= 2; // al guardar esta será la 3ra o más
 
        $atencion = Atencion::create([
            'estudiante_id' => $request->estudiante_id,
            'user_id'       => auth()->id(),
            'motivo_id'     => $request->motivo_id,
            'fecha'         => $request->fecha,
            'diagnostico'   => $request->diagnostico,
            'tratamiento'   => $request->tratamiento,
            'observaciones' => $request->observaciones,
            'es_recurrente' => $esRecurrente,
            'derivado'      => $request->boolean('derivado'),
        ]);
        NotificacionEstudianteService::notificarAtencionCompletada($atencion);
        $mensaje = $esRecurrente
            ? '⚠️ Atención registrada. CASO RECURRENTE detectado para este motivo.'
            : 'Atención registrada correctamente.';
 
        return redirect()->route('atenciones.show', $atencion)->with('success', $mensaje);
    }
 
    public function show(Atencion $atencion) {
        $atencion->load(['estudiante.fichaMedica', 'motivo.categoria', 'enfermera']);
        return view('atenciones.show', compact('atencion'));
    }
 
    public function edit(Atencion $atencion) {
        $categorias  = CategoriaMotivo::with('motivos')->get();
        $estudiantes = Estudiante::orderBy('apellidos')->get();
        return view('atenciones.edit', compact('atencion', 'categorias', 'estudiantes'));
    }
 
    public function update(Request $request, Atencion $atencion) {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'motivo_id'     => 'required|exists:motivos,id',
            'fecha'         => 'required|date',
            'diagnostico'   => 'nullable|string',
            'tratamiento'   => 'nullable|string',
            'observaciones' => 'nullable|string',
            'derivado'      => 'nullable|boolean',
        ]);
 
        $atencion->update([
            'estudiante_id' => $request->estudiante_id,
            'motivo_id'     => $request->motivo_id,
            'fecha'         => $request->fecha,
            'diagnostico'   => $request->diagnostico,
            'tratamiento'   => $request->tratamiento,
            'observaciones' => $request->observaciones,
            'derivado'      => $request->boolean('derivado'),
        ]);
 
        return redirect()->route('atenciones.show', $atencion)
            ->with('success', 'Atención actualizada correctamente.');
    }
 
    public function destroy(Atencion $atencion) {
        $atencion->delete();
        return redirect()->route('atenciones.index')
            ->with('success', 'Atención eliminada correctamente.');
    }
 
    // Historial completo de un estudiante
    public function historial(Estudiante $estudiante) {
        $atenciones = $estudiante->atenciones()
            ->with(['motivo.categoria', 'enfermera'])
            ->orderByDesc('fecha')
            ->get();
        return view('atenciones.historial', compact('estudiante', 'atenciones'));
    }
 
    // Reporte para imprimir
    public function reporte(Estudiante $estudiante) {
        $estudiante->load('fichaMedica', 'carrera');
        $atenciones = $estudiante->atenciones()
            ->with(['motivo.categoria', 'enfermera'])
            ->orderByDesc('fecha')
            ->get();
        return view('atenciones.reporte', compact('estudiante', 'atenciones'));
    }
}
