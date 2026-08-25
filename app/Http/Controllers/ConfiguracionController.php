<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Único formulario para que el ADMINISTRADOR ajuste horario laboral, si el
 * domingo es laborable, y la hora límite de la garita — valores que antes
 * estaban fijos en config/papeletas.php. No es un resource porque no hay
 * "varios registros", es un único conjunto de ajustes (patrón edit/update
 * sin index/create/destroy).
 */
class ConfiguracionController extends Controller
{
    public function edit(): View
    {
        return view('admin.configuracion.edit', [
            'horarioInicio' => Configuracion::obtener('horario_laboral_inicio', '07:00'),
            'horarioFin' => Configuracion::obtener('horario_laboral_fin', '19:00'),
            'domingoLaborable' => Configuracion::obtenerBool('domingo_laborable'),
            'horaLimiteGarita' => Configuracion::obtener('hora_limite_registro_garita', '17:00'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'horario_laboral_inicio' => ['required', 'date_format:H:i'],
            'horario_laboral_fin' => ['required', 'date_format:H:i', 'after:horario_laboral_inicio'],
            'domingo_laborable' => ['nullable', 'boolean'],
            'hora_limite_registro_garita' => ['required', 'date_format:H:i'],
        ]);

        Configuracion::actualizar('horario_laboral_inicio', $data['horario_laboral_inicio']);
        Configuracion::actualizar('horario_laboral_fin', $data['horario_laboral_fin']);
        Configuracion::actualizar('domingo_laborable', $request->boolean('domingo_laborable') ? '1' : '0');
        Configuracion::actualizar('hora_limite_registro_garita', $data['hora_limite_registro_garita']);

        return redirect()->route('configuracion.edit')->with('status', 'Configuración actualizada.');
    }
}
