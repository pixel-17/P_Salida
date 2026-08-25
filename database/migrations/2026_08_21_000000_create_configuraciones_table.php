<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de configuración editable por el ADMINISTRADOR desde el sistema.
 * Reemplaza los valores que antes vivían fijos en config/papeletas.php:
 * horario laboral, si el domingo es laborable, y la hora límite de la
 * garita. Key-value simple porque son pocos valores y de tipos distintos
 * (hora, booleano); no amerita columnas propias.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->string('valor');
            $table->timestamps();
        });

        // Semilla con los mismos valores que tenía config/papeletas.php,
        // para que el cambio no altere el comportamiento actual del
        // sistema el día que se despliegue.
        DB::table('configuraciones')->insert([
            ['clave' => 'horario_laboral_inicio', 'valor' => '07:00', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'horario_laboral_fin', 'valor' => '19:00', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'domingo_laborable', 'valor' => '0', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'hora_limite_registro_garita', 'valor' => '17:00', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
