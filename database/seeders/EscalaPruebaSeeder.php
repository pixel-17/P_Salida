<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Escala de prueba pedida: 2 sedes, 2 RRHH, 3 vigilantes, 2 admin,
 * 4 jefes (uno por área) con 4 trabajadores cada uno = 16 trabajadores.
 * Total de usuarios nuevos: 2+2+3+4+16 = 27.
 *
 * Requiere que RoleSeeder ya haya corrido (crea los roles de Spatie que
 * aquí se asignan con syncRoles). Idempotente: se puede correr varias
 * veces sin duplicar nada, gracias a updateOrCreate() por email/dni.
 */
class EscalaPruebaSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Sedes ----------
        $sedeCentral = Sede::firstOrCreate(
            ['nombre' => 'Sede Central'],
            ['direccion' => 'Av. Principal 100', 'estado' => true]
        );
        $sedeNorte = Sede::firstOrCreate(
            ['nombre' => 'Sede Norte'],
            ['direccion' => 'Av. Norte 200', 'estado' => true]
        );

        // ---------- Áreas + cargo genérico por área (una por cada jefe) ----------
        $areas = collect(['Administración', 'Operaciones', 'Logística', 'Sistemas'])
            ->map(fn (string $nombre) => Area::firstOrCreate(['nombre' => $nombre]));

        $cargoJefe = $areas->mapWithKeys(fn (Area $area) => [
            $area->id => Cargo::firstOrCreate([
                'area_id' => $area->id,
                'nombre' => "Jefe de {$area->nombre}",
            ]),
        ]);

        $cargoTrabajador = $areas->mapWithKeys(fn (Area $area) => [
            $area->id => Cargo::firstOrCreate([
                'area_id' => $area->id,
                'nombre' => "Asistente de {$area->nombre}",
            ]),
        ]);

        $cargoRrhh = Cargo::firstOrCreate(['area_id' => $areas->first()->id, 'nombre' => 'Analista de RRHH']);
        $cargoAdmin = Cargo::firstOrCreate(['area_id' => $areas->first()->id, 'nombre' => 'Administrador de Sistema']);
        $cargoVigilante = Cargo::firstOrCreate(['area_id' => $areas->first()->id, 'nombre' => 'Vigilancia']);

        $password = Hash::make('password');
        $dni = 20000000; // contador simple para DNIs únicos de prueba, no repite con DemoDataSeeder (10000000) ni TestDataSeeder.

        // ---------- 2 Administradores ----------
        for ($i = 1; $i <= 2; $i++) {
            $admin = User::updateOrCreate(
                ['email' => "admin{$i}@prueba.test"],
                [
                    'name' => "Admin Prueba {$i}",
                    'password' => $password,
                    'dni' => (string) ($dni++),
                    'cargo_id' => $cargoAdmin->id,
                    'sede_id' => $i === 1 ? $sedeCentral->id : $sedeNorte->id,
                    'email_verified_at' => now(),
                    'estado' => true,
                ]
            );
            $admin->syncRoles('ADMINISTRADOR');
        }

        // ---------- 2 RRHH ----------
        for ($i = 1; $i <= 2; $i++) {
            $rrhh = User::updateOrCreate(
                ['email' => "rrhh{$i}@prueba.test"],
                [
                    'name' => "RRHH Prueba {$i}",
                    'password' => $password,
                    'dni' => (string) ($dni++),
                    'cargo_id' => $cargoRrhh->id,
                    'sede_id' => $i === 1 ? $sedeCentral->id : $sedeNorte->id,
                    'email_verified_at' => now(),
                    'estado' => true,
                ]
            );
            $rrhh->syncRoles('RRHH');
        }

        // ---------- 3 Vigilantes ----------
        // PapeletaPolicy::marcarComoVigilante exige sede_id === sede_id de la
        // papeleta, así que un vigilante SIEMPRE necesita sede fija (ver
        // StoreUserRequest::withValidator). Uno en Central, uno en Norte,
        // uno extra en Central para tener cobertura en dos turnos.
        $sedesVigilante = [$sedeCentral->id, $sedeNorte->id, $sedeCentral->id];
        foreach ($sedesVigilante as $i => $sedeId) {
            $n = $i + 1;
            $vigilante = User::updateOrCreate(
                ['email' => "vigilante{$n}@prueba.test"],
                [
                    'name' => "Vigilante Prueba {$n}",
                    'password' => $password,
                    'dni' => (string) ($dni++),
                    'cargo_id' => $cargoVigilante->id,
                    'sede_id' => $sedeId,
                    'email_verified_at' => now(),
                    'estado' => true,
                ]
            );
            $vigilante->syncRoles('VIGILANTE');
        }

        // ---------- 4 Jefes, cada uno con 4 trabajadores ----------
        // area_id/sede_id del trabajador se derivan siempre del jefe (mismo
        // criterio que EquipoController::store), así que cada trabajador
        // hereda la sede de su jefe.
        $areas->values()->each(function (Area $area, int $index) use (
            $sedeCentral, $sedeNorte, $cargoJefe, $cargoTrabajador, $password, &$dni
        ) {
            $numJefe = $index + 1;
            $sedeDelJefe = $numJefe % 2 === 1 ? $sedeCentral : $sedeNorte;

            $jefe = User::updateOrCreate(
                ['email' => "jefe{$numJefe}@prueba.test"],
                [
                    'name' => "Jefe Prueba {$numJefe} ({$area->nombre})",
                    'password' => $password,
                    'dni' => (string) ($dni++),
                    'cargo_id' => $cargoJefe[$area->id]->id,
                    'sede_id' => $sedeDelJefe->id,
                    'email_verified_at' => now(),
                    'estado' => true,
                ]
            );
            $jefe->syncRoles('JEFE');

            for ($t = 1; $t <= 4; $t++) {
                $trabajador = User::updateOrCreate(
                    ['email' => "trabajador{$numJefe}_{$t}@prueba.test"],
                    [
                        'name' => "Trabajador {$numJefe}.{$t} ({$area->nombre})",
                        'password' => $password,
                        'dni' => (string) ($dni++),
                        'cargo_id' => $cargoTrabajador[$area->id]->id,
                        'sede_id' => $sedeDelJefe->id,
                        'jefe_id' => $jefe->id,
                        'email_verified_at' => now(),
                        'estado' => true,
                    ]
                );
                $trabajador->syncRoles('TRABAJADOR');
            }
        });

        $this->command?->info('Escala de prueba lista: 2 sedes, 2 admin, 2 RRHH, 3 vigilantes, 4 jefes, 16 trabajadores. Password para todos: "password"');
    }
}
