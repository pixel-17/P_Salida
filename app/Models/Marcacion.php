<?php

namespace App\Models;

use App\Enums\TipoMarcacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Marcacion extends Model
{
    use HasFactory;

    // Eloquent pluraliza "Marcacion" como "marcacions" (no maneja bien el
    // español); se fija explícitamente el nombre real de la tabla.
    protected $table = 'marcaciones';

    protected $fillable = [
        'papeleta_id', 'tipo', 'registrado_por_user_id',
    ];

    protected $casts = [
        'tipo' => TipoMarcacion::class,
    ];

    public function papeleta(): BelongsTo
    {
        return $this->belongsTo(Papeleta::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }
}
