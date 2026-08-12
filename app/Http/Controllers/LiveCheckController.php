<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\Motivo;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class LiveCheckController extends Controller
{
    private const MODELOS = [
        'areas' => Area::class,
        'cargos' => Cargo::class,
        'sedes' => Sede::class,
        'motivos' => Motivo::class,
        'users' => User::class,
    ];

    public function __invoke(string $tabla): JsonResponse
    {
        abort_unless(array_key_exists($tabla, self::MODELOS), 404);

        $modelo = self::MODELOS[$tabla];

        return response()->json([
            'count' => $modelo::count(),
            'ultimo_cambio' => $modelo::max('updated_at'),
        ]);
    }
}
