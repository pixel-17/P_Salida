<?php

namespace App\Http\Controllers;

use App\Models\Motivo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MotivoController extends Controller
{
    public function index(): View
    {
        return view('admin.motivos.index', [
            'motivos' => Motivo::orderBy('nombre')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.motivos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validarDatos($request);

        Motivo::create($data);

        return redirect()->route('motivos.index')->with('status', 'Motivo creado.');
    }

    public function edit(Motivo $motivo): View
    {
        return view('admin.motivos.edit', compact('motivo'));
    }

    public function update(Request $request, Motivo $motivo): RedirectResponse
    {
        $data = $this->validarDatos($request);
        $data['estado'] = $request->boolean('estado');

        $motivo->update($data);

        return redirect()->route('motivos.index')->with('status', 'Motivo actualizado.');
    }

    public function destroy(Motivo $motivo): RedirectResponse
    {
        $motivo->update(['estado' => false]);

        return back()->with('status', 'Motivo desactivado.');
    }

    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'requiere_documento' => ['boolean'],
            'goce_haber' => ['boolean'],
            'max_horas' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
