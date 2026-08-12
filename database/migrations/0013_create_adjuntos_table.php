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
