<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * rol se guarda como string (snapshot de "JEFE"/"RRHH" al momento de la
 * acción), no como FK a un catálogo de roles: si el usuario cambia de rol
 * después, el historial de aprobaciones no debe cambiar retroactivamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flujo_aprobaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('papeleta_id')->constrained('papeletas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users');
            $table->string('rol', 50)->comment('JEFE, RRHH — snapshot del rol al momento de la acción');
            $table->enum('accion', ['APROBADO', 'RECHAZADO', 'OBSERVADO']);
            $table->text('comentario')->nullable();

            $table->timestamps();

            $table->index('papeleta_id', 'idx_flujo_papeleta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flujo_aprobaciones');
    }
};
