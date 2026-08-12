<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CargoController extends Controller
{
    public function index(): View
    {
        return view('admin.cargos.index', [
            'cargos' => Cargo::with('area')->orderBy('nombre')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.cargos.create', [
            'areas' => \App\Models\Area::activas()->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'area_id' => ['required', 'exists:areas,id'],
            'nombre' => ['required', 'string', 'max:150'],
        ]);

        Cargo::create($data);

        return redirect()->route('cargos.index')->with('status', 'Cargo creado.');
    }

    public function edit(Cargo $cargo): View
    {
        return view('admin.cargos.edit', [
            'cargo' => $cargo,
            'areas' => \App\Models\Area::activas()->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Cargo $cargo): RedirectResponse
    {
        $data = $request->validate([
            'area_id' => ['required', 'exists:areas,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'estado' => ['boolean'],
        ]);

        $cargo->update($data);

        return redirect()->route('cargos.index')->with('status', 'Cargo actualizado.');
    }

    public function destroy(Cargo $cargo): RedirectResponse
    {
        $cargo->update(['estado' => false]);

        return back()->with('status', 'Cargo desactivado.');
    }
}
