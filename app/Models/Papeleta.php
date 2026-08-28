<?php

namespace App\Models;

use App\Enums\EstadoPapeleta;
use App\Enums\TipoMarcacion;
use App\Enums\TipoObservacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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

    /**
     * Días de diferencia entre hoy y la fecha_salida autorizada.
     * Positivo: la salida es en el futuro (aún faltan N días).
     * Cero: la salida es hoy — único día válido para marcar salida.
     * Negativo: la fecha ya pasó (caso raro, cubierto por
     * papeletas:cancelar-no-presentadas a las 23:55, pero se calcula
     * igual por si se consulta antes de que corra ese barrido).
     */
    public function diasParaSalida(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->fecha_salida->copy()->startOfDay(), false);
    }

    /**
     * Único día en que corresponde marcar salida: exactamente el día
     * solicitado. La hora programada es solo una referencia/estimado —el
     * trabajador puede presentarse antes o después dentro de ese mismo
     * día— pero la FECHA no es negociable: no se marca salida ni un día
     * antes ni un día después de lo autorizado.
     */
    public function esHoyFechaDeSalida(): bool
    {
        return $this->diasParaSalida() === 0;
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

        $transcurridos = $this->updated_at->diffInSeconds(now(), true);

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

    /**
     * Estado de sustento documental para el reporte "Sustentos": no es una
     * columna de BD, se deriva de si el motivo exige documento y de la
     * ÚLTIMA observación tipo JUSTIFICACION levantada sobre esta papeleta
     * (ver ObservarPapeletaAction/ResponderObservacionAction).
     *
     * Regla clave: si tras responder una observación (atendida=true) el
     * revisor levanta OTRA observación JUSTIFICACION después, esa nueva
     * queda como "la última" — el documento anterior no sirvió. Por eso
     * basta con mirar la más reciente, no hace falta recorrer todo el
     * historial para saber si el sustento vigente es válido.
     */
    public function estadoSustento(): array
    {
        if (! $this->motivo?->requiere_documento) {
            return ['codigo' => 'no_requiere', 'label' => 'No requiere', 'color' => 'gray'];
        }

        $observaciones = $this->relationLoaded('observacionesJustificacion')
            ? $this->observacionesJustificacion
            : $this->observaciones()->where('tipo', TipoObservacion::JUSTIFICACION->value)->orderBy('created_at')->get();

        $ultima = $observaciones->last();

        if (! $ultima) {
            return $this->adjuntos->isNotEmpty()
                ? ['codigo' => 'presentado', 'label' => 'Presentado', 'color' => 'green']
                : ['codigo' => 'sin_pedir', 'label' => 'Sin observar', 'color' => 'gray'];
        }

        if (! $ultima->atendida) {
            return ['codigo' => 'pendiente', 'label' => 'Pendiente de subsanar', 'color' => 'red'];
        }

        // Respondida y nadie volvió a pedir después: quedó aceptada.
        return ['codigo' => 'aceptado', 'label' => 'Subsanado', 'color' => 'green'];
    }

    public function observacionesJustificacion(): HasMany
    {
        return $this->hasMany(Observacion::class)
            ->where('tipo', TipoObservacion::JUSTIFICACION->value)
            ->orderBy('created_at');
    }

    /**
     * Fin efectivo para calcular "horas fuera": la marcación de RETORNO si
     * existe. Si NO existe, el sistema ya no debe seguir contando con
     * now() indefinidamente — el vigilante deja de poder registrar
     * retornos pasada `Configuracion::horaLimiteGarita()` (ver
     * MarcarRetornoVigilanteAction), así que una vez pasado ese corte del
     * día de la salida se considera cerrado ahí para efectos de reporte,
     * aunque el trabajador nunca haya marcado.
     *
     * Esto NO borra evidencia de que nunca marcó: la papeleta sigue
     * mostrándose VENCIDA con etiquetaVencimiento() = "Sin retorno" tal
     * cual (ver MarcarPapeletasVencidasCommand). Solo evita que "horas
     * fuera" crezca sin límite día tras día (antes: 118h+ para una
     * papeletas de varios días atrás sin retorno; ahora: tope real de un
     * solo día laboral).
     */
    public function finEfectivoParaHoras(): ?Carbon
    {
        $marcSalida = $this->marcacion(TipoMarcacion::SALIDA);

        if (! $marcSalida) {
            return null;
        }

        $marcRetorno = $this->marcacion(TipoMarcacion::RETORNO);

        if ($marcRetorno) {
            return $marcRetorno->created_at;
        }

        $cierreGarita = Carbon::parse(
            $this->fecha_salida->format('Y-m-d').' '.Configuracion::horaLimiteGarita()
        );

        return now()->min($cierreGarita);
    }

    /**
     * Horas fuera redondeadas a 1 decimal, o null si nunca marcó salida
     * (nada que contar). Wrapper de finEfectivoParaHoras() para los
     * lugares que solo necesitan el número final de una papeleta (tabla
     * de detalle, exportación); los rankings que SUMAN horas de muchas
     * papeletas siguen trabajando con finEfectivoParaHoras() directo para
     * no perder precisión al redondear antes de sumar.
     *
     * diffInMinutes() con $absolute=true a propósito: desde Carbon 3 estos
     * métodos devuelven diferencia CON signo por defecto (antes siempre
     * era positiva). "Horas fuera" nunca puede ser negativo por
     * definición, así que se fuerza el valor absoluto explícitamente —
     * sin esto, un caso raro (p.ej. la marcación de salida real cae
     * después del cierre de garita usado como tope, ver
     * finEfectivoParaHoras()) mostraba horas negativas sin sentido.
     */
    public function horasFuera(): ?float
    {
        $marcSalida = $this->marcacion(TipoMarcacion::SALIDA);
        $fin = $this->finEfectivoParaHoras();

        if (! $marcSalida || ! $fin) {
            return null;
        }

        return round($marcSalida->created_at->diffInMinutes($fin, true) / 60, 1);
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
     * Bandeja "Pendientes" del trabajador: lo de hoy (para que vea su
     * salida del día aunque ya esté finalizada) más cualquier papeleta
     * suya que siga en trámite sin importar la fecha (p. ej. una
     * observada la semana pasada que aún necesita su atención). Lo
     * resuelto y archivado de días anteriores solo aparece en "todas".
     */
    public function scopePendientesDelTrabajador($query, int $trabajadorId)
    {
        return $query->where('trabajador_id', $trabajadorId)
            ->where(function ($q) {
                $q->whereDate('fecha_salida', now()->toDateString())
                    ->orWhereHas('estado', fn ($q2) => $q2->whereNotIn('codigo', [
                        EstadoPapeleta::FINALIZADO->value,
                        EstadoPapeleta::RECHAZADO->value,
                        EstadoPapeleta::CANCELADO->value,
                        EstadoPapeleta::VENCIDA->value,
                    ]));
            })
            ->latest('fecha_salida');
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
