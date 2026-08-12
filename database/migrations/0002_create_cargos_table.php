<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->string('nombre', 150);
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->index('area_id', 'idx_cargo_area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
