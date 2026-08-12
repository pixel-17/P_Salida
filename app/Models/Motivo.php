<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Motivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'requiere_documento', 'goce_haber', 'max_horas', 'estado',
    ];

    protected $casts = [
        'requiere_documento' => 'boolean',
        'goce_haber' => 'boolean',
        'estado' => 'boolean',
    ];

    public function papeletas(): HasMany
    {
        return $this->hasMany(Papeleta::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', true);
    }
}
