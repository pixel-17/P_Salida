<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora genérica de acciones administrativas (crear/actualizar/
 * desactivar/eliminar) sobre catálogos y usuarios. No reemplaza
 * historial_papeletas (eso audita el flujo de negocio de una papeleta
 * puntual); esto audita quién tocó qué recurso administrativo y cuándo,
 * para trazabilidad institucional del panel de Administrador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 20)->comment('CREAR, ACTUALIZAR, DESACTIVAR, REACTIVAR, ELIMINAR');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('auditable_label')->nullable()->comment('Snapshot legible del recurso, ej. nombre del área, para no depender de que el registro siga existiendo');
            $table->json('cambios')->nullable()->comment('Solo los campos que cambiaron: {campo: [antes, despues]}');
            $table->timestamp('created_at')->nullable();

            $table->index(['auditable_type', 'auditable_id'], 'idx_audit_auditable');
            $table->index('user_id', 'idx_audit_user');
            $table->index('created_at', 'idx_audit_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
