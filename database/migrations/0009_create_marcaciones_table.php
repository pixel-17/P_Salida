<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Toda marcación la registra un vigilante de puerta (QR o código) — no
 * existe marcado por GPS del propio trabajador. registrado_por_user_id es
 * NOT NULL porque siempre hay un vigilante detrás de cada marcación; es la
 * trazabilidad real, no hace falta duplicar auditoría con IP/user-agent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('papeleta_id')->constrained('papeletas')->cascadeOnDelete();
            $table->enum('tipo', ['SALIDA', 'RETORNO']);
            $table->foreignId('registrado_por_user_id')->constrained('users');

            $table->timestamps();

            $table->unique(['papeleta_id', 'tipo'], 'uq_marcacion_papeleta_tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marcaciones');
    }
};
