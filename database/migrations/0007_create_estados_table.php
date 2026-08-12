<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * No incluye RETORNO_MARCADO: ese código existió en la v0 como paso
 * intermedio "trabajador marcó retorno por GPS, falta que el jefe
 * confirme". Como el marcado ahora es 100% responsabilidad del vigilante
 * (que finaliza directo), ese paso nunca existió en esta versión.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique()
                ->comment('SOLICITADO, APROBADO_JEFE, APROBADO_RRHH, EN_CURSO, FINALIZADO, RECHAZADO, OBSERVADO, VENCIDA, CANCELADO');
            $table->string('nombre', 100);
            $table->string('color', 30)->nullable()->comment('Clase CSS o #HEX para badges');
            $table->unsignedSmallInteger('orden');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados');
    }
};
