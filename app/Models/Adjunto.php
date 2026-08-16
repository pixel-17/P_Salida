<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Adjunto extends Model
{
    use HasFactory;

    protected $fillable = ['papeleta_id', 'observacion_id', 'nombre_original', 'archivo', 'extension', 'peso'];

    public function papeleta(): BelongsTo
    {
        return $this->belongsTo(Papeleta::class);
    }

    /**
     * Presente solo si este adjunto fue subido como respuesta a una
     * observación tipo JUSTIFICACION (ver AdjuntoController::store). En
     * ese caso el adjunto queda como evidencia y no se puede eliminar,
     * ver PapeletaPolicy::eliminarAdjunto.
     */
    public function observacion(): BelongsTo
    {
        return $this->belongsTo(Observacion::class);
    }

    public function url(): string
    {
        return Storage::disk('local')->temporaryUrl($this->archivo, now()->addMinutes(10));
    }

    protected static function booted(): void
    {
        static::deleting(function (Adjunto $adjunto) {
            Storage::disk('local')->delete($adjunto->archivo);
        });
    }
}
