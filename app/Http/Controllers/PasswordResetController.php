<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Mostrar formulario de solicitud
     */
    public function showRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Enviar email de recuperación
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.exists' => 'No existe una cuenta con este correo.'
        ]);

        $user = User::where('email', $request->email)->first();

        // Generar token
        $token = Str::random(64);
        
        // Limpiar tokens antiguos
        PasswordReset::where('user_id', $user->id)->delete();

        // Crear nuevo token válido por 30 minutos
        PasswordReset::create([
            'user_id' => $user->id,
            'token' => $token,
            'expira_en' => Carbon::now()->addMinutes(30),
        ]);

        // Enviar email
        $enlace = route('password.reset', ['token' => $token]);
        
        Mail::send('emails.recuperar-password', 
            ['user' => $user, 'enlace' => $enlace], 
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Recuperar Contraseña - S-Topic');
            }
        );

        return redirect()->route('login')
            ->with('success', 'Se envió un link de recuperación a tu correo');
    }

    /**
     * Mostrar formulario de reset
     */
    public function showResetForm($token)
    {
        $reset = PasswordReset::where('token', $token)
            ->where('expira_en', '>', Carbon::now())
            ->first();

        if (!$reset) {
            return redirect()->route('login')
                ->with('error', 'Link de recuperación expirado o inválido');
        }

        return view('auth.reset-password', compact('token'));
    }

    /**
     * Procesar reset de contraseña
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $reset = PasswordReset::where('token', $request->token)
            ->where('expira_en', '>', Carbon::now())
            ->first();

        if (!$reset) {
            return back()->with('error', 'Link inválido o expirado');
        }

        // Actualizar contraseña
        $user = $reset->usuario;
        $user->update(['password' => Hash::make($request->password)]);

        // Eliminar token
        $reset->delete();

        return redirect()->route('login')
            ->with('success', 'Contraseña restablecida. Inicia sesión');
    }
}