<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Requests\StoreTrabajadorRequest;
use App\Models\Cargo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EquipoController extends Controller
{
    /**
     * Jefe: solo ve a sus trabajadores (jefe_id = él mismo), sin filtro de
     * jefe porque para él ya es implícito. Admin: ve todos los
     * trabajadores del sistema, con un filtro para acotar por jefe.
     */
    public function index(Request $request): View
    {
        $this->authorize('verEquipo', User::class);

        $esAdmin = $request->user()->esAdmin();
        $jefeSeleccionado = $esAdmin ? $request->get('jefe_id') : $request->user()->id;

        $trabajadores = User::query()
            ->role(RolUsuario::TRABAJADOR->value)
            ->with(['cargo.area', 'sede', 'jefe'])
            ->when($jefeSeleccionado, fn ($q) => $q->where('jefe_id', $jefeSeleccionado))
            ->when($request->get('buscar'), function ($q, $buscar) {
                $q->where(fn ($q) => $q->where('name', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%"));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('equipo.index', [
            'trabajadores' => $trabajadores,
            'esAdmin' => $esAdmin,
            'jefeSeleccionado' => $jefeSeleccionado,
            'buscar' => $request->get('buscar'),
            'jefes' => $esAdmin ? User::role(RolUsuario::JEFE->value)->orderBy('name')->get() : null,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('crearTrabajador', User::class);

        return view('equipo.create', [
            'esAdmin' => $request->user()->esAdmin(),
            'cargos' => Cargo::activos()->orderBy('nombre')->get(),
            'jefes' => $request->user()->esAdmin()
                ? User::role(RolUsuario::JEFE->value)->with('sede')->orderBy('name')->get()
                : null,
            // Para un jefe creando su propio trabajador, esto es solo
            // informativo (la sede real se fija en store() a partir de su
            // propia sede_id).
            'miSede' => $request->user()->sede,
        ]);
    }

    public function store(StoreTrabajadorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        // El jefe_id NUNCA sale del input cuando quien crea es un jefe: se
        // fuerza al propio usuario autenticado para que la relación quede
        // atada de inmediato, sin depender de lo que venga del formulario.
        $data['jefe_id'] = $request->user()->esAdmin()
            ? $data['jefe_id']
            : $request->user()->id;

        // Regla dura: la sede del trabajador SIEMPRE es la de su jefe. No
        // se pide en el formulario ni se confía en nada que venga del
        // cliente — así se evita el bug de crear un trabajador con una
        // sede distinta a la de su propio jefe.
        $data['sede_id'] = User::find($data['jefe_id'])?->sede_id;

        $trabajador = User::create($data);
        $trabajador->assignRole(RolUsuario::TRABAJADOR);

        return redirect()->route('equipo.index')->with('status', 'Trabajador creado.');
    }

    public function edit(User $trabajador): View
    {
        $this->authorize('editarTrabajador', $trabajador);

        return view('equipo.edit', [
            'trabajador' => $trabajador,
            'cargos' => Cargo::activos()->orderBy('nombre')->get(),
            'jefes' => User::role(RolUsuario::JEFE->value)
                ->with('sede')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $trabajador): RedirectResponse
    {
        $this->authorize('editarTrabajador', $trabajador);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$trabajador->id],
            'dni' => ['required', 'digits:8', 'unique:users,dni,'.$trabajador->id],
            'telefono' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', \Illuminate\Validation\Rules\Password::defaults()],
            'cargo_id' => ['nullable', 'exists:cargos,id'],
            'jefe_id' => ['required', 'exists:users,id'],
            'estado' => ['sometimes', 'boolean'],
        ]);

        $data['password'] = filled($data['password'] ?? null)
            ? Hash::make($data['password'])
            : $trabajador->password;

        // Misma regla dura que en store(): la sede sigue siempre a la del
        // jefe. Si el admin cambia el jefe_id, la sede se recalcula sola.
        $data['sede_id'] = User::find($data['jefe_id'])?->sede_id;

        $trabajador->update($data);

        return redirect()->route('equipo.index')->with('status', 'Trabajador actualizado.');
    }

    public function destroy(User $trabajador): RedirectResponse
    {
        $this->authorize('eliminarTrabajador', $trabajador);

        // Mismo criterio que UserController: baja lógica, nunca hard-delete
        // (rompería el historial de papeletas de ese trabajador).
        $trabajador->update(['estado' => false]);
        $trabajador->delete();

        return back()->with('status', 'Trabajador desactivado.');
    }
}
