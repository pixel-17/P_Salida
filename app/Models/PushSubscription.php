<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'endpoint', 'endpoint_hash', 'p256dh', 'auth_token', 'user_agent', 'activo',
    ];

    protected $hidden = ['p256dh', 'auth_token'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Array en el formato que espera minishlink/web-push.
     */
    public function toWebPushSubscription(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'publicKey' => $this->p256dh,
            'authToken' => $this->auth_token,
        ];
    }
}
