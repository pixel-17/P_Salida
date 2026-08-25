<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Configuración editable por el ADMINISTRADOR desde el sistema (ver
 * ConfiguracionController). Reemplaza los valores que antes estaban fijos
 * en config/papeletas.php: horario laboral, si el domingo es laborable, y
 * la hora límite de la garita.
 *
 * Se cachea indefinidamente porque se lee en cada request que crea una
 * papeleta o registra en garita; update() invalida la caché de la clave
 * tocada, así el cambio se refleja de inmediato sin esperar al TTL.
 */
class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = ['clave', 'valor'];

    public static function obtener(string $clave, ?string $default = null): ?string
    {
        return Cache::rememberForever(
            "configuracion:{$clave}",
            fn () => static::where('clave', $clave)->value('valor') ?? $default
        );
    }

    public static function obtenerBool(string $clave, bool $default = false): bool
    {
        $valor = static::obtener($clave, $default ? '1' : '0');

        return $valor === '1';
    }

    public static function actualizar(string $clave, string $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);

        Cache::forget("configuracion:{$clave}");
    }

    /**
     * Días no laborables en formato Carbon (0 = domingo), calculado a
     * partir de domingo_laborable. Si más adelante se quiere dar control
     * sobre otros días, este es el único punto que cambiaría.
     */
    public static function diasNoLaborables(): array
    {
        return static::obtenerBool('domingo_laborable') ? [] : [0];
    }
}
