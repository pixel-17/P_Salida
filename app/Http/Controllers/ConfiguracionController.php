<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Único formulario para que el ADMINISTRADOR ajuste horario laboral y si el
 * domingo es laborable — valores que antes estaban fijos en
 * config/papeletas.php. No es un resource porque no hay "varios registros",
 * es un único conjunto de ajustes (patrón edit/update sin index/create/destroy).
 *
 * La hora límite de garita NO es un campo propio del formulario: se deriva
 * siempre de horario_laboral_fin (ver Configuracion::horaLimiteGarita()),
 * para que el admin no pueda guardar una combinación inconsistente entre
 * ambos horarios.
 */
class ConfiguracionController extends Controller
{
    public function edit(): View
    {
        return view('admin.configuracion.edit', [
            'horarioInicio' => Configuracion::obtener('horario_laboral_inicio', '07:00'),
            'horarioFin' => Configuracion::obtener('horario_laboral_fin', '19:00'),
            'domingoLaborable' => Configuracion::obtenerBool('domingo_laborable'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'horario_laboral_inicio' => ['required', 'date_format:H:i'],
            'horario_laboral_fin' => ['required', 'date_format:H:i', 'after:horario_laboral_inicio'],
            'domingo_laborable' => ['nullable', 'boolean'],
        ]);

        Configuracion::actualizar('horario_laboral_inicio', $data['horario_laboral_inicio']);
        Configuracion::actualizar('horario_laboral_fin', $data['horario_laboral_fin']);
        Configuracion::actualizar('domingo_laborable', $request->boolean('domingo_laborable') ? '1' : '0');

        return redirect()->route('configuracion.edit')->with('status', 'Configuración actualizada.');
    }
}
