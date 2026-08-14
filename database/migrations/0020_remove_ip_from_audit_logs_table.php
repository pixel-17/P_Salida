<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Se decidió no registrar la IP en la bitácora de auditoría (no se va a
 * usar). Si ya corriste la migración 0019 antes de este cambio, esto
 * elimina la columna; si corrés todo desde cero, la 0019 ya no la crea
 * y este archivo simplemente no encuentra nada que borrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('audit_logs', 'ip')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn('ip');
            });
        }
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('ip', 45)->nullable();
        });
    }
};
