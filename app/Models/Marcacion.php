<?php

namespace App\Models;

use App\Enums\TipoMarcacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

// @property explícito: sin esto, Larastan infiere created_at como
// Carbon\Carbon (tipo base de doctrine/dbal) en vez de
// Illuminate\Support\Carbon (el que Eloquent realmente instancia vía el
// cast 'datetime'), y rompe el tipo de retorno declarado en cualquier
// método que reenvíe este valor (ver Papeleta::finEfectivoParaHoras()).
/**
 * @property Carbon $created_at
 */
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
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<Papeleta, $this> */
    public function papeleta(): BelongsTo
    {
        return $this->belongsTo(Papeleta::class);
    }

    /** @return BelongsTo<User, $this> */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }
}
