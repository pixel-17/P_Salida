<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\RolUsuario;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

// Implementa MustVerifyEmail: VerifyEmailController llama
// hasVerifiedEmail()/markEmailAsVerified(), que solo existen si el modelo
// implementa esta interfaz (más el trait). Sin esto el flujo de
// verificación de correo falla con error fatal en runtime, no es un
// hallazgo únicamente de PHPStan.
class User extends Authenticatable implements MustVerifyEmail
{
    use Auditable, HasFactory, HasRoles, MustVerifyEmailTrait, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'dni', 'telefono',
        'cargo_id', 'sede_id', 'jefe_id', 'estado',
        'must_change_password', 'aviso_password_mostrado',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'estado' => 'boolean',
        'must_change_password' => 'boolean',
        'aviso_password_mostrado' => 'boolean',
    ];

    /** @return BelongsTo<Cargo, $this> */
    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    /** @return BelongsTo<Sede, $this> */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    /** @return BelongsTo<User, $this> */
    public function jefe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jefe_id');
    }

    /** @return HasMany<User, $this> */
    public function subordinados(): HasMany
    {
        return $this->hasMany(User::class, 'jefe_id');
    }

    /** @return HasMany<Papeleta, $this> */
    public function papeletas(): HasMany
    {
        return $this->hasMany(Papeleta::class, 'trabajador_id');
    }

    /** @return HasMany<Papeleta, $this> */
    public function papeletasPorAprobar(): HasMany
    {
        return $this->hasMany(Papeleta::class, 'jefe_id');
    }

    /** @return HasMany<FlujoAprobacion, $this> */
    public function flujoAprobaciones(): HasMany
    {
        return $this->hasMany(FlujoAprobacion::class, 'usuario_id');
    }

    /** @return HasMany<NotificacionSistema, $this> */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(NotificacionSistema::class);
    }

    /** @return HasMany<PushSubscription, $this> */
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

    /**
     * Marca que ya se le mostró el aviso de "tu contraseña sigue siendo tu
     * DNI" — una sola vez en toda la vida del usuario, acepte o cancele.
     * No toca must_change_password (ese sigue reflejando si la contraseña
     * real cambió o no).
     */
    public function marcarAvisoPasswordMostrado(): void
    {
        if (! $this->aviso_password_mostrado) {
            $this->forceFill(['aviso_password_mostrado' => true])->save();
        }
    }
}
