<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vincula un adjunto a la observación que respondió, cuando aplica. Un
 * adjunto subido como respuesta a una observación JUSTIFICACION queda
 * marcado como evidencia (ver PapeletaPolicy::eliminarAdjunto) y ya no se
 * puede borrar, sin importar en qué estado caiga después la papeleta.
 * Nullable porque la mayoría de adjuntos no responden a una observación
 * (se suben al crear la papeleta cuando el motivo lo exige).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adjuntos', function (Blueprint $table) {
            $table->foreignId('observacion_id')->nullable()->after('papeleta_id')
                ->constrained('observaciones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('adjuntos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('observacion_id');
        });
    }
};
