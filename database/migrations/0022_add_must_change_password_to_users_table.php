<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Todo usuario creado por un Jefe o el Administrador nace con su DNI como
 * contraseña (ver EquipoController/UserController). Este flag obliga a
 * cambiarla la primera vez que inicia sesión; se apaga solo, una vez, al
 * completar ese cambio (ver ForzarCambioPasswordController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
