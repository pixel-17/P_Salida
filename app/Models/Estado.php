<?php

namespace App\Models;

use App\Enums\EstadoPapeleta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estado extends Model
{
    public $timestamps = true;

    protected $fillable = ['codigo', 'nombre', 'color', 'orden'];

    public function papeletas(): HasMany
    {
        return $this->hasMany(Papeleta::class);
    }

    public static function porCodigo(EstadoPapeleta $codigo): self
    {
        return static::where('codigo', $codigo->value)->firstOrFail();
    }
}
