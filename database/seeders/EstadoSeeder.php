<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            ['codigo' => 'SOLICITADO', 'nombre' => 'Solicitado', 'color' => 'gray', 'orden' => 1],
            ['codigo' => 'APROBADO_JEFE', 'nombre' => 'Aprobado por Jefe', 'color' => 'blue', 'orden' => 2],
            ['codigo' => 'APROBADO_RRHH', 'nombre' => 'Aprobado por RRHH', 'color' => 'indigo', 'orden' => 3],
            ['codigo' => 'EN_CURSO', 'nombre' => 'En Curso', 'color' => 'yellow', 'orden' => 4],
            ['codigo' => 'FINALIZADO', 'nombre' => 'Finalizado', 'color' => 'green', 'orden' => 5],
            ['codigo' => 'RECHAZADO', 'nombre' => 'Rechazado', 'color' => 'red', 'orden' => 6],
            ['codigo' => 'OBSERVADO', 'nombre' => 'Observado', 'color' => 'orange', 'orden' => 7],
            ['codigo' => 'VENCIDA', 'nombre' => 'Vencida', 'color' => 'red', 'orden' => 8],
            ['codigo' => 'CANCELADO', 'nombre' => 'Cancelado', 'color' => 'slategray', 'orden' => 9],
        ];

        foreach ($estados as $estado) {
            DB::table('estados')->updateOrInsert(
                ['codigo' => $estado['codigo']],
                array_merge($estado, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
