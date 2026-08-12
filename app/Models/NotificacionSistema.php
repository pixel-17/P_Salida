<?php

namespace App\Models;

use App\Enums\CanalNotificacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionSistema extends Model
{
    use HasFactory;

    protected $table = 'notificaciones_sistema';

    protected $fillable = [
        'user_id', 'papeleta_id', 'tipo', 'canal', 'titulo', 'mensaje', 'enviada_at', 'leida_at',
    ];

    protected $casts = [
        'canal' => CanalNotificacion::class,
        'enviada_at' => 'datetime',
        'leida_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function papeleta(): BelongsTo
    {
        return $this->belongsTo(Papeleta::class);
    }

    public function scopeNoLeidas($query)
    {
        return $query->whereNull('leida_at');
    }

    public function marcarLeida(): void
    {
        if ($this->leida_at === null) {
            $this->update(['leida_at' => now()]);
        }
    }
}
