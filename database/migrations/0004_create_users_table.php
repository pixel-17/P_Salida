<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * No hay auto-registro: los usuarios los crea RRHH manualmente. Roles y
 * permisos van con spatie/laravel-permission (correr sus migraciones justo
 * después de esta), no con una columna de rol propia ni middleware custom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // Todo usuario creado por un Jefe o el Administrador nace con su
            // DNI como contraseña (ver EquipoController/UserController).
            // Este flag obliga a cambiarla la primera vez que inicia sesión;
            // se apaga solo, una vez, al completar ese cambio (ver
            // ForzarCambioPasswordController).
            $table->boolean('must_change_password')->default(true);
            // A diferencia de must_change_password (que refleja si la
            // contraseña real sigue siendo el DNI), este flag es solo "¿ya
            // vio el aviso alguna vez?". Se marca en true la primera vez que
            // se muestra el modal, sin importar si el usuario acepta o
            // cancela, y nunca se vuelve a poner en false: el aviso se
            // muestra una única vez en la vida del usuario, no por sesión.
            $table->boolean('aviso_password_mostrado')->default(false);
            $table->string('dni', 8)->unique();
            $table->string('telefono', 20)->nullable();

            $table->foreignId('cargo_id')->nullable()
                ->constrained('cargos')->nullOnDelete();
            $table->foreignId('sede_id')->nullable()
                ->constrained('sedes')->nullOnDelete();
            $table->foreignId('jefe_id')->nullable()
                ->constrained('users')->nullOnDelete()
                ->comment('Jefe inmediato para flujo de aprobación');

            $table->boolean('estado')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('jefe_id', 'idx_user_jefe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
