<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialPapeleta extends Model
{
    use HasFactory;

    protected $table = 'historial_papeletas';

    protected $fillable = [
        'papeleta_id', 'usuario_id', 'accion', 'estado_anterior_id', 'estado_nuevo_id', 'descripcion',
    ];

    public function papeleta(): BelongsTo
    {
        return $this->belongsTo(Papeleta::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function estadoAnterior(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_anterior_id');
    }

    public function estadoNuevo(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_nuevo_id');
    }

    /**
     * Sigue recibiendo los códigos de estado como string (ej. 'FINALIZADO')
     * para no tener que tocar cada Action que la llama; acá adentro se
     * resuelven a los ids reales de la tabla `estados`.
     */
    public static function registrar(
        Papeleta $papeleta,
        ?User $usuario,
        string $accion,
        ?string $estadoAnteriorCodigo,
        ?string $estadoNuevoCodigo,
        ?string $descripcion = null,
    ): self {
        return static::create([
            'papeleta_id' => $papeleta->id,
            'usuario_id' => $usuario?->id,
            'accion' => $accion,
            'estado_anterior_id' => $estadoAnteriorCodigo
                ? Estado::where('codigo', $estadoAnteriorCodigo)->value('id')
                : null,
            'estado_nuevo_id' => $estadoNuevoCodigo
                ? Estado::where('codigo', $estadoNuevoCodigo)->value('id')
                : null,
            'descripcion' => $descripcion,
        ]);
    }
}
