<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'accion', 'auditable_type', 'auditable_id',
        'auditable_label', 'cambios', 'created_at',
    ];

    protected $casts = [
        'cambios' => 'array',
        'created_at' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Nombre legible del tipo de recurso para el panel de auditoría
     * (Área, Cargo, Sede, Motivo, Usuario), sin exponer el FQCN al admin.
     */
    public function tipoLegible(): string
    {
        return match ($this->auditable_type) {
            Area::class => 'Área',
            Cargo::class => 'Cargo',
            Sede::class => 'Sede',
            Motivo::class => 'Motivo',
            User::class => 'Usuario',
            default => class_basename($this->auditable_type),
        };
    }
}
