<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('papeleta_id')->constrained('papeletas')->cascadeOnDelete();
            // Vincula un adjunto a la observación que respondió, cuando
            // aplica. Un adjunto subido como respuesta a una observación
            // JUSTIFICACION queda marcado como evidencia (ver
            // PapeletaPolicy::eliminarAdjunto) y ya no se puede borrar, sin
            // importar en qué estado caiga después la papeleta. Nullable
            // porque la mayoría de adjuntos no responden a una observación
            // (se suben al crear la papeleta cuando el motivo lo exige).
            $table->foreignId('observacion_id')->nullable()
                ->constrained('observaciones')->nullOnDelete();
            $table->string('nombre_original', 255);
            $table->string('archivo', 255);
            $table->string('extension', 20);
            $table->unsignedBigInteger('peso');
            $table->timestamps();

            $table->index('papeleta_id', 'idx_adjunto_papeleta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjuntos');
    }
};
