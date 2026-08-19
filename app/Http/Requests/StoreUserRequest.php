<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ya protegido por middleware role:ADMINISTRADOR en la ruta
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        $reglasJefeId = ['nullable', 'exists:users,id'];
        if ($userId) {
            $reglasJefeId[] = Rule::notIn([$userId]);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'dni' => ['required', 'digits:8', Rule::unique('users', 'dni')->ignore($userId)],
            'telefono' => ['nullable', 'string', 'max:20'],
            // En creación no se pide: se autogenera = DNI (ver
            // UserController::store()). En edición queda opcional para
            // que el admin pueda resetearla manualmente si hace falta.
            'password' => ['nullable', Password::defaults()],
            'cargo_id' => ['nullable', 'exists:cargos,id'],
            'sede_id' => ['nullable', 'exists:sedes,id'],
            'jefe_id' => $reglasJefeId,
            'rol' => ['required', new Enum(RolUsuario::class)],
            'estado' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Un vigilante sin sede_id no puede confirmar nada (PapeletaPolicy
     * compara sede_id exacto), así que para este rol sede_id pasa a ser
     * obligatorio aunque en general sea opcional.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('rol') === RolUsuario::VIGILANTE->value && ! $this->filled('sede_id')) {
                $validator->errors()->add('sede_id', 'Un vigilante necesita una sede asignada.');
            }

            if ($this->input('rol') === RolUsuario::TRABAJADOR->value && ! $this->filled('jefe_id')) {
                $validator->errors()->add('jefe_id', 'Un trabajador necesita un jefe asignado (su sede se deriva de él).');
            }
        });
    }
}
