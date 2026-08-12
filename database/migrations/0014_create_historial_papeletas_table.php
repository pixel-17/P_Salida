<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mejora de normalización vs. v0: estado_anterior/estado_nuevo eran
 * strings libres que duplicaban estados.codigo (podían desincronizarse si
 * se renombraba un código). Acá son FK reales hacia `estados`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_papeletas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('papeleta_id')->constrained('papeletas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 150);
            $table->foreignId('estado_anterior_id')->nullable()
                ->constrained('estados')->nullOnDelete();
            $table->foreignId('estado_nuevo_id')->nullable()
                ->constrained('estados')->nullOnDelete();
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index('papeleta_id', 'idx_historial_papeleta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_papeletas');
    }
};
