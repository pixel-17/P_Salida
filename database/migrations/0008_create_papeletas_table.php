<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * area_id / sede_id / jefe_id son un snapshot deliberado del trabajador al
 * momento de crear la papeleta (no una relación "en vivo"): si el
 * trabajador cambia de área/sede/jefe después, la papeleta histórica debe
 * seguir mostrando con quién/dónde fue el trámite en su momento. No es
 * redundancia a normalizar — es requisito de auditoría.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('papeletas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique()->comment('Ej: PAP-2026-00001');

            $table->foreignId('trabajador_id')->constrained('users');
            $table->foreignId('jefe_id')->nullable()->constrained('users')
                ->comment('Jefe que debe aprobar (snapshot del jefe_id del trabajador al crear)');
            $table->foreignId('area_id')->constrained('areas')
                ->comment('Snapshot del area_id del trabajador al crear');
            $table->foreignId('sede_id')->nullable()->constrained('sedes')
                ->comment('Snapshot del sede_id del trabajador al crear');
            $table->foreignId('motivo_id')->constrained('motivos');
            $table->foreignId('estado_id')->constrained('estados');

            $table->string('destino', 255);
            $table->text('motivo_detalle')->nullable();

            $table->date('fecha_salida');
            $table->time('hora_salida_programada');
            $table->time('hora_retorno_programada')->nullable();
            $table->timestamp('alerta_salida_enviada_at')->nullable()
                ->comment('Evita que el comando de alerta (cada 5 min) renotifique la misma papeleta mientras sigue sin marcar salida');

            $table->timestamps();
            $table->softDeletes();

            $table->index('trabajador_id', 'idx_papeleta_trabajador');
            $table->index(['trabajador_id', 'fecha_salida'], 'idx_papeleta_trabajador_fecha');
            $table->index(['jefe_id', 'estado_id'], 'idx_papeleta_jefe_estado');
            $table->index('estado_id', 'idx_papeleta_estado');
            $table->index('fecha_salida', 'idx_papeleta_fecha');
        });

        // Blueprint no expone check() nativo en todas las versiones; se agrega vía SQL crudo.
        DB::statement('
            ALTER TABLE papeletas
            ADD CONSTRAINT chk_retorno_posterior
            CHECK (hora_retorno_programada IS NULL OR hora_retorno_programada > hora_salida_programada)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('papeletas');
    }
};
