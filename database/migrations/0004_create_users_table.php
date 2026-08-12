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
