<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, Auditable;

    protected $fillable = [
        'name', 'email', 'password', 'dni', 'telefono',
        'cargo_id', 'sede_id', 'jefe_id', 'estado',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'estado' => 'boolean',
    ];

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function jefe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jefe_id');
    }

    public function subordinados(): HasMany
    {
        return $this->hasMany(User::class, 'jefe_id');
    }

    public function papeletas(): HasMany
    {
        return $this->hasMany(Papeleta::class, 'trabajador_id');
    }

    public function papeletasPorAprobar(): HasMany
    {
        return $this->hasMany(Papeleta::class, 'jefe_id');
    }

    public function flujoAprobaciones(): HasMany
    {
        return $this->hasMany(FlujoAprobacion::class, 'usuario_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(NotificacionSistema::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function esJefe(): bool
    {
        return $this->hasRole(RolUsuario::JEFE);
    }

    public function esRrhh(): bool
    {
        return $this->hasRole(RolUsuario::RRHH);
    }

    public function esTrabajador(): bool
    {
        return $this->hasRole(RolUsuario::TRABAJADOR);
    }

    public function esVigilante(): bool
    {
        return $this->hasRole(RolUsuario::VIGILANTE);
    }

    public function esAdmin(): bool
    {
        return $this->hasRole(RolUsuario::ADMINISTRADOR);
    }
}