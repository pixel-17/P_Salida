<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Papeleta;
use App\Models\Sede;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalUsuarios' => User::count(),
            'totalAreas' => Area::count(),
            'totalSedes' => Sede::count(),
            'papeletasPorEstado' => Papeleta::query()
                ->join('estados', 'estados.id', '=', 'papeletas.estado_id')
                ->selectRaw('estados.nombre, estados.color, count(*) as total')
                ->groupBy('estados.nombre', 'estados.color')
                ->orderByDesc('total')
                ->get(),
        ]);
    }
}
