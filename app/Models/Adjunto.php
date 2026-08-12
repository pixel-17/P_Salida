<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Adjunto extends Model
{
    use HasFactory;

    protected $fillable = ['papeleta_id', 'nombre_original', 'archivo', 'extension', 'peso'];

    public function papeleta(): BelongsTo
    {
        return $this->belongsTo(Papeleta::class);
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
