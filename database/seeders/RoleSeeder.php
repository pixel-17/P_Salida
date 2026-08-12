<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RolUsuario::cases() as $rol) {
            Role::firstOrCreate(['name' => $rol->value, 'guard_name' => 'web']);
        }
    }
}