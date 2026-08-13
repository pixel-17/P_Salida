<?php

namespace App\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Bitácora automática para los CRUDs administrativos (Área, Cargo, Sede,
 * Motivo, Usuario). Solo registra cuando hay un usuario autenticado
 * haciendo el cambio desde la web/API — así los seeders y factories
 * (que corren por consola, sin sesión) no ensucian la auditoría.
 *
 * Los controladores de estos catálogos hacen "baja lógica" con
 * ->update(['estado' => false]) en vez de destroy() real; por eso el hook
 * de "updated" distingue ese caso especial (DESACTIVAR/REACTIVAR) del
 * resto de ediciones (ACTUALIZAR), para que el panel de auditoría sea
 * legible en vez de mostrar siempre "ACTUALIZAR".
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::registrarAuditoria($model, 'CREAR');
        });

        static::updated(function ($model) {
            $cambios = static::cambiosRelevantes($model);

            if (empty($cambios)) {
                return;
            }

            $accion = 'ACTUALIZAR';

            if (array_key_exists('estado', $cambios) && count($cambios) === 1) {
                $accion = $cambios['estado'][1] ? 'REACTIVAR' : 'DESACTIVAR';
            }

            static::registrarAuditoria($model, $accion, $cambios);
        });

        static::deleted(function ($model) {
            static::registrarAuditoria($model, 'ELIMINAR');
        });
    }

    /**
     * Solo compara los atributos que realmente cambiaron de valor,
     * excluyendo timestamps y la contraseña (nunca se audita en texto,
     * ni siquiera hasheada).
     */
    protected static function cambiosRelevantes($model): array
    {
        $ignorados = ['updated_at', 'created_at', 'password', 'remember_token'];

        $cambios = [];
        foreach ($model->getChanges() as $campo => $nuevo) {
            if (in_array($campo, $ignorados, true)) {
                continue;
            }
            $cambios[$campo] = [$model->getOriginal($campo), $nuevo];
        }

        return $cambios;
    }

    protected static function registrarAuditoria($model, string $accion, array $cambios = []): void
    {
        if (! Auth::check()) {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'accion' => $accion,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'auditable_label' => static::etiquetaLegible($model),
            'cambios' => $cambios ?: null,
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }

    protected static function etiquetaLegible($model): ?string
    {
        return $model->nombre ?? $model->name ?? $model->codigo ?? null;
    }
}
