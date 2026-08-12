<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function index(): View
    {
        return view('admin.areas.index', [
            'areas' => Area::withCount('cargos')->orderBy('nombre')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.areas.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'siglas' => ['nullable', 'string', 'max:20'],
            'descripcion' => ['nullable', 'string'],
        ]);

        Area::create($data);

        return redirect()->route('areas.index')->with('status', 'Área creada.');
    }

    public function edit(Area $area): View
    {
        return view('admin.areas.edit', compact('area'));
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'siglas' => ['nullable', 'string', 'max:20'],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['boolean'],
        ]);

        $area->update($data);

        return redirect()->route('areas.index')->with('status', 'Área actualizada.');
    }

    public function destroy(Area $area): RedirectResponse
    {
        // Baja lógica: nunca borrar un área con cargos/usuarios/papeletas asociadas.
        $area->update(['estado' => false]);

        return back()->with('status', 'Área desactivada.');
    }
}
