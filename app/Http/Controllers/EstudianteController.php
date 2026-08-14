<?php
namespace App\Http\Controllers;
use App\Models\Estudiante;
use App\Models\FichaMedica;
use App\Models\Carrera;
use Illuminate\Http\Request;

class EstudianteController extends Controller {

    public function index(Request $request) {
        $query = Estudiante::with(['carrera','fichaMedica']);
        if ($request->filled('dni'))
            $query->where('dni','like','%'.$request->dni.'%');
        if ($request->filled('nombre'))
            $query->where(fn($q) => $q->where('nombres','like','%'.$request->nombre.'%')
                                      ->orWhere('apellidos','like','%'.$request->nombre.'%'));
        $estudiantes = $query->orderBy('apellidos')->paginate(15);
        return view('estudiantes.index', compact('estudiantes'));
    }

    public function create() {
        $carreras = Carrera::orderBy('nombre')->get();
        return view('estudiantes.create', compact('carreras'));
    }

    public function store(Request $request) {
        $request->validate([
            'dni'              => 'required|digits:8|unique:estudiantes,dni',
            'nombres'          => 'required|string|max:100',
            'apellidos'        => 'required|string|max:100',
            'email'         => 'required|email|unique:estudiantes', 
            'fecha_nacimiento' => 'nullable|date|before:'.now()->subYears(12)->format('Y-m-d'),
            'genero'           => 'nullable|in:M,F',
            'telefono'         => 'nullable|string|max:15',
            'carrera_id'       => 'nullable|exists:carreras,id',
            'semestre'         => 'nullable|string|max:20',
            'tipo_sangre'           => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'peso'                  => 'nullable|numeric|min:1|max:300',
            'talla'                 => 'nullable|numeric|min:0.5|max:100',
            'alergias'              => 'nullable|string',
            'enfermedades_previas'  => 'nullable|string',
            'medicamentos_actuales' => 'nullable|string',
            'telefono_emergencia'   => 'nullable|string|max:15',
            'contacto_emergencia'   => 'nullable|string|max:100',
        ], [
            'fecha_nacimiento.before' => 'El estudiante debe tener al menos 12 años.',
            'dni.digits'              => 'El DNI debe tener exactamente 8 dígitos.',
            'dni.unique'              => 'Ya existe un estudiante con ese DNI.',
        ]);

        $estudiante = Estudiante::create($request->only([
            'dni','nombres','apellidos','email','fecha_nacimiento','genero','telefono','carrera_id','semestre',
        ]));

        FichaMedica::create(array_merge(
            $request->only(['tipo_sangre','peso','talla','alergias','enfermedades_previas',
                            'medicamentos_actuales','telefono_emergencia','contacto_emergencia']),
            ['estudiante_id' => $estudiante->id]
        ));

        return redirect()->route('estudiantes.show', $estudiante)
            ->with('success', "Estudiante {$estudiante->nombre_completo} registrado correctamente.");
    }

    public function show(Estudiante $estudiante) {
        $estudiante->load(['carrera','fichaMedica','atenciones.motivo','atenciones.enfermera']);
        $atenciones = $estudiante->atenciones()->with(['motivo','enfermera'])->orderByDesc('fecha')->paginate(10);
        return view('estudiantes.show', compact('estudiante','atenciones'));
    }

    public function edit(Estudiante $estudiante) {
        $carreras = Carrera::orderBy('nombre')->get();
        $estudiante->load('fichaMedica');
        return view('estudiantes.edit', compact('estudiante','carreras'));
    }

    public function update(Request $request, Estudiante $estudiante) {
        $request->validate([
            'dni'              => 'required|digits:8|unique:estudiantes,dni,'.$estudiante->id,
            'nombres'          => 'required|string|max:100',
            'apellidos'        => 'required|string|max:100',
            'email'            => 'required|email|unique:estudiantes,email,' . $estudiante->id,  
            'fecha_nacimiento' => 'nullable|date|before:'.now()->subYears(12)->format('Y-m-d'),
            'genero'           => 'nullable|in:M,F',
            'telefono'         => 'nullable|string|max:15',
            'carrera_id'       => 'nullable|exists:carreras,id',
            'semestre'         => 'nullable|string|max:20',
            'tipo_sangre'           => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'peso'                  => 'nullable|numeric|min:1|max:300',
            'talla'                 => 'nullable|numeric|min:0.5|max:2.5',
            'alergias'              => 'nullable|string',
            'enfermedades_previas'  => 'nullable|string',
            'medicamentos_actuales' => 'nullable|string',
            'telefono_emergencia'   => 'nullable|string|max:15',
            'contacto_emergencia'   => 'nullable|string|max:100',
        ]);

        $estudiante->update($request->only([
            'dni','nombres','apellidos','email','fecha_nacimiento','genero','telefono','carrera_id','semestre',
        ]));

        $estudiante->fichaMedica()->updateOrCreate(
            ['estudiante_id' => $estudiante->id],
            $request->only(['tipo_sangre','peso','talla','alergias','enfermedades_previas',
                            'medicamentos_actuales','telefono_emergencia','contacto_emergencia'])
        );

        return redirect()->route('estudiantes.show', $estudiante)
            ->with('success', "Datos de {$estudiante->nombre_completo} actualizados correctamente.");
    }

    public function destroy(Estudiante $estudiante) {
        $nombre = $estudiante->nombre_completo;
        $estudiante->delete();
        return redirect()->route('estudiantes.index')
            ->with('success', "Estudiante {$nombre} eliminado correctamente.");
    }

    public function buscarPorDni(Request $request) {
        $estudiante = Estudiante::with(['fichaMedica','carrera'])
            ->where('dni', $request->dni)->first();
        if (!$estudiante)
            return response()->json(['error' => 'no_encontrado'], 404);
        return response()->json($estudiante);
    }
}
