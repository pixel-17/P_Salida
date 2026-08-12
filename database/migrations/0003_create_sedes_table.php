<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * latitud/longitud se mantienen sin alimentar ningún cálculo hoy: quedan
 * reservadas para una futura vista de mapa/geolocalización. No agregar
 * radio_permitido ni tiene_vigilante — el marcado de salida/retorno es
 * 100% responsabilidad del vigilante de puerta en todas las sedes, sin
 * excepción ni tolerancia GPS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('direccion', 255)->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sedes');
    }
};
