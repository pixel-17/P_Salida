<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Pantalla de cambio de contraseña, ofrecida (no obligatoria) a un
 * usuario cuya contraseña sigue siendo la inicial (= su DNI, asignada por
 * su Jefe/Administrador). Se llega acá desde la notificación modal que
 * aparece una vez al acceder (ver layouts.partials.alerta-cambio-password),
 * nunca por un redirect forzado.
 */
class ForzarCambioPasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.cambiar-password-obligatorio');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ])->save();

        return redirect()->route('dashboard')->with('status', 'Contraseña actualizada.');
    }
}
