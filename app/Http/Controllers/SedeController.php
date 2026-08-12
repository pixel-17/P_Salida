<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SedeController extends Controller
{
    public function index(): View
    {
        return view('admin.sedes.index', [
            'sedes' => Sede::orderBy('nombre')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.sedes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validarDatos($request);

        Sede::create($data);

        return redirect()->route('sedes.index')->with('status', 'Sede creada.');
    }

    public function edit(Sede $sede): View
    {
        return view('admin.sedes.edit', compact('sede'));
    }

    public function update(Request $request, Sede $sede): RedirectResponse
    {
        $data = $this->validarDatos($request);
        $data['estado'] = $request->boolean('estado');

        $sede->update($data);

        return redirect()->route('sedes.index')->with('status', 'Sede actualizada.');
    }

    public function destroy(Sede $sede): RedirectResponse
    {
        $sede->update(['estado' => false]);

        return back()->with('status', 'Sede desactivada.');
    }

    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
        ]);
    }
}
