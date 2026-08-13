<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Cargo;
use App\Models\Motivo;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    private const TIPOS = [
        'Area' => Area::class,
        'Cargo' => Cargo::class,
        'Sede' => Sede::class,
        'Motivo' => Motivo::class,
        'User' => User::class,
    ];

    public function index(Request $request): View
    {
        $filtros = $request->only(['tipo', 'accion', 'usuario_id', 'desde', 'hasta']);

        $logs = AuditLog::query()
            ->with('usuario')
            ->when($filtros['tipo'] ?? null, fn ($q, $tipo) => $q->where('auditable_type', self::TIPOS[$tipo] ?? $tipo))
            ->when($filtros['accion'] ?? null, fn ($q, $accion) => $q->where('accion', $accion))
            ->when($filtros['usuario_id'] ?? null, fn ($q, $usuarioId) => $q->where('user_id', $usuarioId))
            ->when($filtros['desde'] ?? null, fn ($q, $desde) => $q->whereDate('created_at', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($q, $hasta) => $q->whereDate('created_at', '<=', $hasta))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.audit-log.index', [
            'logs' => $logs,
            'filtros' => $filtros,
            'tipos' => array_keys(self::TIPOS),
            'usuariosConActividad' => User::whereIn('id', AuditLog::query()->distinct()->pluck('user_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
