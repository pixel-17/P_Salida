<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrabajadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crearTrabajador', \App\Models\User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'dni' => ['required', 'digits:8', Rule::unique('users', 'dni')],
            'telefono' => ['nullable', 'string', 'max:20'],
            // La contraseña ya no se pide acá: se autogenera = DNI en
            // EquipoController::store(). Ver EquipoController::update()
            // para el reseteo manual (ahí sí valida contra Password::defaults()).
            'cargo_id' => ['nullable', 'exists:cargos,id'],
            // sede_id ya no se pide en el formulario: se deriva siempre
            // del jefe (ver EquipoController::store) para que nunca quede
            // un trabajador en una sede distinta a la de su jefe.
            // Solo el administrador elige a qué jefe asignar el trabajador
            // (ver EquipoController::store) — para un jefe creando el
            // suyo, este campo ni siquiera se muestra en el formulario y
            // el valor recibido se ignora: el jefe_id real siempre se fija
            // en el controlador a partir del usuario autenticado.
            'jefe_id' => [
                $this->user()->esAdmin() ? 'required' : 'nullable',
                'exists:users,id',
            ],
        ];
    }

    /**
     * jefe_id debe apuntar a un usuario que realmente tenga rol JEFE (no
     * cualquier id de usuario existente).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->user()->esAdmin() && $this->filled('jefe_id')) {
                $esJefe = \App\Models\User::role(RolUsuario::JEFE->value)
                    ->whereKey($this->input('jefe_id'))
                    ->exists();

                if (! $esJefe) {
                    $validator->errors()->add('jefe_id', 'El usuario seleccionado no tiene rol de Jefe.');
                }
            }
        });
    }
}
