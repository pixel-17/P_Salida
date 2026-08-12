<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->text('endpoint')->comment('URL de push que entrega el navegador al suscribirse');
            $table->char('endpoint_hash', 64)->comment('SHA-256 del endpoint; permite indexarlo y hacerlo único');
            $table->string('p256dh')->comment('Clave pública de cifrado de la suscripción');
            $table->string('auth_token')->comment('Token de autenticación de la suscripción');

            $table->text('user_agent')->nullable();
            $table->boolean('activo')->default(true)
                ->comment('Se pone en FALSE si el navegador responde 410/expirada, sin borrar el registro');
            $table->timestamps();

            $table->unique('endpoint_hash', 'uq_push_endpoint_hash');
            $table->index('user_id', 'idx_push_sub_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
