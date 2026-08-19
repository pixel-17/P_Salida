<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Requests\StoreUserRequest;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['buscar', 'rol', 'area_id', 'sede_id', 'estado']);

        $users = User::with(['cargo.area', 'sede', 'jefe'])
            ->when($filtros['buscar'] ?? null, function ($q, $buscar) {
                $q->where(fn ($q) => $q->where('name', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%"));
            })
            ->when($filtros['rol'] ?? null, fn ($q, $rol) => $q->role($rol))
            ->when($filtros['area_id'] ?? null, fn ($q, $areaId) => $q->whereHas('cargo', fn ($q) => $q->where('area_id', $areaId)))
            ->when($filtros['sede_id'] ?? null, fn ($q, $sedeId) => $q->where('sede_id', $sedeId))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado === 'activo'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filtros' => $filtros,
            'roles' => RolUsuario::cases(),
            'areas' => Area::activas()->orderBy('nombre')->get(),
            'sedes' => Sede::where('estado', true)->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', $this->datosFormulario());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $rol = $data['rol'];
        unset($data['rol']);

        // Igual que en EquipoController: la sede de un trabajador SIEMPRE
        // es la de su jefe, nunca la que venga (o no) del formulario.
        if ($rol === RolUsuario::TRABAJADOR->value) {
            $data['sede_id'] = User::find($data['jefe_id'])?->sede_id;
        }

        // Contraseña inicial = DNI, igual que en EquipoController: el
        // admin no elige contraseñas ajenas para ningún rol. Se fuerza el
        // cambio en el primer login (must_change_password).
        $data['password'] = Hash::make($data['dni']);
        $data['must_change_password'] = true;

        $user = User::create($data);
        $user->assignRole($rol);

        return redirect()->route('users.index')->with('status', 'Usuario creado.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', array_merge(['user' => $user], $this->datosFormulario($user)));
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $rol = $data['rol'];
        unset($data['rol']);

        // Misma regla que en store(): si el (nuevo) rol es TRABAJADOR, la
        // sede se recalcula siempre a partir del jefe elegido.
        if ($rol === RolUsuario::TRABAJADOR->value) {
            $data['sede_id'] = User::find($data['jefe_id'])?->sede_id;
        }

        if (filled($data['password'] ?? null)) {
            $data['password'] = Hash::make($data['password']);
            $data['must_change_password'] = true;
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles($rol);

        return redirect()->route('users.index')->with('status', 'Usuario actualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Puro toggle: NO borra (ni soft-delete). Reactivar se hace desde
        // el checkbox "Activo" en el formulario de edición. Así el email
        // y el DNI de alguien desactivado se pueden seguir viendo/usando
        // para ese mismo registro sin quedar bloqueados por un delete().
        $user->update(['estado' => false]);

        return back()->with('status', 'Usuario desactivado.');
    }

    /**
     * Borrado real (soft-delete): a diferencia de destroy(), esto SÍ deja
     * el email/dni bloqueados para siempre (por el unique index), así que
     * requiere confirmación explícita + contraseña del propio admin.
     */
    public function eliminar(Request $request, User $user): RedirectResponse
    {
        $request->validateWithBag('eliminarUsuario'.$user->id, [
            'password' => ['required', 'current_password'],
        ]);

        $user->update(['estado' => false]);
        $user->delete();

        return back()->with('status', 'Usuario eliminado.');
    }

    private function datosFormulario(?User $excluir = null): array
    {
        return [
            'areas' => Area::activas()->orderBy('nombre')->get(),
            'cargos' => Cargo::activos()->orderBy('nombre')->get(),
            'sedes' => Sede::where('estado', true)->orderBy('nombre')->get(),
            'jefes' => User::role(RolUsuario::JEFE->value)
                ->with('sede')
                ->when($excluir, fn ($q) => $q->whereKeyNot($excluir->id))
                ->orderBy('name')->get(),
        ];
    }
}