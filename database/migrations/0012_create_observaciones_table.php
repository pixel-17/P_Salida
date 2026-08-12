<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('papeleta_id')->constrained('papeletas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users');
            $table->enum('tipo', ['ADMINISTRATIVA', 'JUSTIFICACION'])
                ->comment('ADMINISTRATIVA = corrige un dato y vuelve directo a RRHH. JUSTIFICACION = requiere sustento y vuelve a pasar por el Jefe. El rechazo NO va aquí, ya está en flujo_aprobaciones.accion=RECHAZADO');
            $table->text('comentario');
            $table->boolean('atendida')->default(false);
            $table->timestamps();

            $table->index('papeleta_id', 'idx_observacion_papeleta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones');
    }
};
