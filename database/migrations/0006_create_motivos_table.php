<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motivos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->boolean('requiere_documento')->default(false);
            $table->boolean('goce_haber')->default(false);
            $table->integer('max_horas')->nullable()
                ->comment('Límite máximo de horas; respaldo del job de vencidas cuando no hay hora_retorno_programada');
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motivos');
    }
};
