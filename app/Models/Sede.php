<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sede extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'nombre', 'direccion', 'latitud', 'longitud', 'estado',
    ];

    protected $casts = [
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'estado' => 'boolean',
    ];

    /** @return HasMany<User, $this> */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Papeleta, $this> */
    public function papeletas(): HasMany
    {
        return $this->hasMany(Papeleta::class);
    }

    public function distanciaHaciaMetros(float $lat, float $lng): float
    {
        $radioTierra = 6371000;
        $lat1 = deg2rad((float) $this->latitud);
        $lat2 = deg2rad($lat);
        $deltaLat = deg2rad($lat - (float) $this->latitud);
        $deltaLng = deg2rad($lng - (float) $this->longitud);

        $a = sin($deltaLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $radioTierra * $c;
    }
}
