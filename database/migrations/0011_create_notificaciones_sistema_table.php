<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Esta es la única tabla de notificaciones del sistema. NO crear también
 * la tabla nativa `notifications` de Laravel (php artisan notifications:table)
 * salvo que en el futuro se agregue un canal ->via() que use 'database':
 * hoy los canales reales son 'sistema' (esta tabla), 'webpush' y 'mail'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_sistema', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('papeleta_id')->nullable()->constrained('papeletas')->cascadeOnDelete();

            $table->string('tipo', 60)
                ->comment('Ej: PAPELETA_PENDIENTE, PAPELETA_OBSERVADA, PAPELETA_RECHAZADA, PAPELETA_AUTORIZADA, RETORNO_PENDIENTE, PAPELETA_VENCIDA, CIERRE_FORMAL');
            $table->enum('canal', ['SISTEMA', 'PUSH', 'CORREO'])->default('SISTEMA');
            $table->string('titulo', 150);
            $table->text('mensaje');

            $table->timestamp('enviada_at')->nullable()->comment('Cuándo se envió realmente; NULL si sigue en cola');
            $table->timestamp('leida_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'leida_at'], 'idx_notif_user_leida');
            $table->index('papeleta_id', 'idx_notif_papeleta');
            $table->index('tipo', 'idx_notif_tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_sistema');
    }
};
