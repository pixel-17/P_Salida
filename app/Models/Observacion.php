<?php

namespace App\Models;

use App\Enums\TipoObservacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Observacion extends Model
{
    use HasFactory;

    protected $table = 'observaciones';

    protected $fillable = ['papeleta_id', 'usuario_id', 'tipo', 'comentario', 'atendida'];

    protected $casts = [
        'tipo' => TipoObservacion::class,
        'atendida' => 'boolean',
    ];

    public function papeleta(): BelongsTo
    {
        return $this->belongsTo(Papeleta::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Adjuntos subidos como respuesta a esta observación (ver
     * AdjuntoController::store, que liga el archivo a la observación
     * JUSTIFICACION pendiente en el momento de subirlo). Usado por el
     * reporte de sustentos para mostrar QUÉ documento respondió cada
     * solicitud, no solo si la papeleta tiene adjuntos en general.
     */
    public function adjuntos(): HasMany
    {
        return $this->hasMany(Adjunto::class);
    }
}
