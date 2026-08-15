<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A diferencia de must_change_password (que sigue reflejando si la
 * contraseña real sigue siendo el DNI), este flag es solo "¿ya vio el
 * aviso alguna vez?". Se marca en true la primera vez que se muestra el
 * modal, sin importar si el usuario acepta o cancela, y nunca se vuelve
 * a poner en false: el aviso se muestra una única vez en la vida del
 * usuario, no una vez por sesión.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('aviso_password_mostrado')->default(false)->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('aviso_password_mostrado');
        });
    }
};
