<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'siglas', 'descripcion', 'estado'];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class);
    }

    public function papeletas(): HasMany
    {
        return $this->hasMany(Papeleta::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', true);
    }
}
