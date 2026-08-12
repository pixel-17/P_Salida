<?php

namespace App\Http\Controllers;

use App\Models\NotificacionSistema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notificaciones = $request->user()
            ->notificaciones()
            ->where('canal', 'SISTEMA')
            ->latest()
            ->limit(15)
            ->get();

        return response()->json([
            'notificaciones' => $notificaciones,
            'no_leidas' => $request->user()->notificaciones()->where('canal', 'SISTEMA')->noLeidas()->count(),
        ]);
    }

    public function marcarLeida(NotificacionSistema $notificacion): JsonResponse
    {
        abort_unless($notificacion->user_id === request()->user()->id, 403);

        $notificacion->marcarLeida();

        return response()->json(['status' => 'ok']);
    }

    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        $request->user()->notificaciones()->noLeidas()->update(['leida_at' => now()]);

        return response()->json(['status' => 'ok']);
    }
}
