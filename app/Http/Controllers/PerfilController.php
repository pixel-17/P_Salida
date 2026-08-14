<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PerfilController extends Controller {
    public function edit() {
        return view('perfil.edit');
    }

    public function updatePassword(Request $request) {
        $request->validate([
            'password_actual' => 'required',
            'password'        => 'required|string|min:8|confirmed',
        ],[
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if (!Hash::check($request->password_actual, auth()->user()->password)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual no es correcta.']);
        }

        auth()->user()->update(['password' => Hash::make($request->password)]);
        return back()->with('success','Contraseña actualizada correctamente.');
    }

    public function updateTema(Request $request) {
        $request->validate(['tema' => 'required|in:light,dark,blue']);
        session(['tema' => $request->tema]);
        return back()->with('success','Tema aplicado correctamente.');
    }
}
