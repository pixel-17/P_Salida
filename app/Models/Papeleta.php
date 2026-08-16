<?php

namespace App\Models;

use App\Enums\EstadoPapeleta;
use App\Enums\TipoMarcacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Papeleta extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo', 'trabajador_id', 'jefe_id', 'area_id', 'sede_id',
        'motivo_id', 'estado_id', 'destino', 'motivo_detalle',
        'fecha_salida', 'hora_salida_programada', 'hora_retorno_programada',
    ];

    protected $casts = [
        'fecha_salida' => 'date',
        'alerta_salida_enviada_at' => 'datetime',
    ];

    // ---------- Relaciones ----------

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trabajador_id');
    }

    public function jefe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jefe_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function motivo(): BelongsTo
    {
        return $this->belongsTo(Motivo::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function marcaciones(): HasMany
    {
        return $this->hasMany(Marcacion::class);
    }

    public function flujoAprobaciones(): HasMany
    {
        return $this->hasMany(FlujoAprobacion::class);
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(Observacion::class);
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(Adjunto::class);
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialPapeleta::class);
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(NotificacionSistema::class);
    }

    // ---------- Helpers de estado ----------

    public function estaEn(EstadoPapeleta $codigo): bool
    {
        return $this->estado->codigo === $codigo->value;
    }

    public function marcacion(TipoMarcacion $tipo): ?Marcacion
    {
        return $this->marcaciones->firstWhere('tipo', $tipo->value);
    }

    public function yaMarcoSalida(): bool
    {
        return $this->marcacion(TipoMarcacion::SALIDA) !== null;
    }

    public function yaMarcoRetorno(): bool
    {
        return $this->marcacion(TipoMarcacion::RETORNO) !== null;
    }

    /**
     * Ventana corta tras entrar a un estado con código visible (aprobado
     * por RRHH, o recién marcada la salida) en la que se oculta el QR.
     * Evita que el trabajador lo muestre dos veces seguidas por error
     * (p.ej. el vigilante escanea salida y, sin querer, escanea otra vez
     * de inmediato) — sobre todo justo al momento de la salida, que es
     * cuando pantalla y vigilante están más apurados.
     *
     * "updated_at" sirve como referencia porque tanto AprobarPapeletaAction
     * como MarcarSalidaVigilanteAction actualizan estado_id (y por lo tanto
     * updated_at) exactamente en el momento de la transición, y nada más
     * vuelve a tocar la papeleta mientras sigue en ese mismo estado.
     */
    public const COOLDOWN_CODIGO_SEGUNDOS = 20;

    public function segundosRestantesParaCodigo(): int
    {
        if (! $this->updated_at) {
            return 0;
        }

        $transcurridos = $this->updated_at->diffInSeconds(now());

        return max(0, self::COOLDOWN_CODIGO_SEGUNDOS - $transcurridos);
    }

    /**
     * "Sin retorno": caso puntual dentro de VENCIDA en que el trabajador sí
     * marcó salida con el vigilante pero nunca marcó retorno. El resto de
     * VENCIDA (nunca llegó a marcar salida, papeleta que se quedó en
     * trámite) se etiqueta como "No se presentó". Es un cálculo derivado
     * de las marcaciones reales, no un estado aparte en la base de datos.
     */
    public function esSinRetorno(): bool
    {
        return $this->estaEn(EstadoPapeleta::VENCIDA) && $this->yaMarcoSalida() && ! $this->yaMarcoRetorno();
    }

    /**
     * Motivo legible para mostrar junto al estado cuando este es VENCIDA:
     * distingue "Sin retorno" (salió y no volvió) de "No se presentó"
     * (nunca llegó a marcar salida). Null para cualquier otro estado.
     */
    public function etiquetaVencimiento(): ?string
    {
        if (! $this->estaEn(EstadoPapeleta::VENCIDA)) {
            return null;
        }

        return $this->esSinRetorno() ? 'Sin retorno' : 'No se presentó';
    }

    // ---------- Scopes para bandejas por rol ----------

    public function scopePendientesDeJefe($query, int $jefeId)
    {
        // Ya no incluye RETORNO_MARCADO: ese paso de confirmación del jefe
        // desapareció junto con el marcado GPS del trabajador (ver
        // PapeletaPolicy::marcar) — ahora el vigilante finaliza directo.
        return $query->where('jefe_id', $jefeId)
            ->whereHas('estado', fn ($q) => $q->where('codigo', EstadoPapeleta::SOLICITADO->value));
    }

    public function scopePendientesDeRrhh($query)
    {
        return $query->whereHas('estado', fn ($q) => $q->where('codigo', EstadoPapeleta::APROBADO_JEFE->value));
    }

    /**
     * Todo lo que le corresponde ver a un jefe: no solo lo pendiente de su
     * decisión, también lo ya resuelto (rechazado, observado, finalizado, etc.)
     * de la gente que le reporta.
     */
    public function scopeDeSuEquipo($query, int $jefeId)
    {
        return $query->where('jefe_id', $jefeId);
    }

    public function scopeDelTrabajador($query, int $trabajadorId)
    {
        return $query->where('trabajador_id', $trabajadorId)->latest('fecha_salida');
    }

    /**
     * Filtros comunes de bandeja: búsqueda por texto, estado, rango de fechas y área.
     * Cada clave es opcional; se aplica solo si viene presente y no vacía.
     */
    public function scopeConFiltros($query, array $filtros)
    {
        return $query
            ->when($filtros['buscar'] ?? null, function ($q, $buscar) {
                $q->where(function ($q) use ($buscar) {
                    $q->where('codigo', 'like', "%{$buscar}%")
                        ->orWhere('destino', 'like', "%{$buscar}%")
                        ->orWhereHas('trabajador', fn ($q) => $q->where('name', 'like', "%{$buscar}%"));
                });
            })
            ->when($filtros['estado_id'] ?? null, fn ($q, $estadoId) => $q->where('estado_id', $estadoId))
            ->when($filtros['area_id'] ?? null, fn ($q, $areaId) => $q->where('area_id', $areaId))
            ->when($filtros['desde'] ?? null, fn ($q, $desde) => $q->whereDate('fecha_salida', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($q, $hasta) => $q->whereDate('fecha_salida', '<=', $hasta));
    }
}
