<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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

    /**
     * Hora límite para que el vigilante confirme salidas/retornos en
     * garita: SIEMPRE derivada de horario_laboral_fin + 10 minutos de
     * cortesía, nunca un valor propio editable por separado.
     *
     * Antes existía como clave independiente ('hora_limite_registro_garita')
     * configurable aparte del horario laboral, lo que permitía al admin
     * guardar una combinación inconsistente (p. ej. límite de garita antes
     * del fin — o incluso antes del inicio — de la jornada), dejando
     * papeletas creadas dentro de horario laboral que el vigilante nunca
     * podía marcar. Al calcularse siempre a partir de horario_laboral_fin
     * queda una sola fuente de verdad y esa inconsistencia ya no es
     * posible.
     */
    public static function horaLimiteGarita(): string
    {
        $horarioFin = static::obtener('horario_laboral_fin', '19:00');

        return Carbon::parse($horarioFin)
            ->addMinutes(10)
            ->format('H:i');
    }
}
