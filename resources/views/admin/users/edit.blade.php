<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Editar Usuario</h2>
    </x-slot>

    @php
        $rolActual = old('rol', $user->getRoleNames()->first());
        $jefeIdInicial = old('jefe_id', $user->jefe_id);
        $sedeInicial = $jefeIdInicial ? optional($jefes->firstWhere('id', $jefeIdInicial))->sede?->nombre : '';
    @endphp
    <form x-data="{ rol: '{{ $rolActual }}', sedeJefe: '{{ $sedeInicial }}' }" method="POST" action="{{ route('users.update', $user) }}" class="glass-panel p-6 space-y-4 max-w-lg animate-fade-in-up">
        @csrf @method('PUT')

        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Nombre completo</label>
            <input type="text" name="name" required value="{{ old('name', $user->name) }}" class="input-glass">
            <x-input-error :messages="$errors->get('name')" />
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Email</label>
            <input type="email" name="email" required value="{{ old('email', $user->email) }}" class="input-glass">
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">DNI</label>
            <input type="text" name="dni" required maxlength="8" value="{{ old('dni', $user->dni) }}" class="input-glass">
            <x-input-error :messages="$errors->get('dni')" />
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Teléfono</label>
            <input type="text" name="telefono" value="{{ old('telefono', $user->telefono) }}" class="input-glass">
            <x-input-error :messages="$errors->get('telefono')" />
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Nueva contraseña</label>
            <input type="password" name="password" class="input-glass">
            <p class="text-xs text-gray-400 mt-1">Déjalo en blanco para no cambiarla. Si la cambias, se le pedirá volver a definirla al iniciar sesión.</p>
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Rol</label>
            <select name="rol" x-model="rol" required class="input-glass">
                @foreach(\App\Enums\RolUsuario::cases() as $rol)
                    <option value="{{ $rol->value }}" @selected($rolActual === $rol->value)>{{ $rol->label() }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('rol')" />
        </div>
        <div x-show="rol !== 'VIGILANTE'" x-cloak>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Cargo</label>
            <select name="cargo_id" class="input-glass">
                <option value="">Sin asignar</option>
                @foreach($cargos as $cargo)
                    <option value="{{ $cargo->id }}" @selected(old('cargo_id', $user->cargo_id) == $cargo->id)>{{ $cargo->nombre }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('cargo_id')" />
        </div>
        <div x-show="!['RRHH', 'ADMINISTRADOR'].includes(rol)" x-cloak>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">
                Sede <span x-show="rol === 'VIGILANTE'" x-cloak class="text-red-500">*</span>
            </label>
            <template x-if="rol === 'TRABAJADOR'">
                <input type="text" :value="sedeJefe || 'Selecciona un jefe primero'" disabled
                       class="input-glass bg-gray-100 text-gray-500 cursor-not-allowed">
            </template>
            <template x-if="rol !== 'TRABAJADOR'">
                <select name="sede_id" class="input-glass">
                    <option value="">Sin asignar</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}" @selected(old('sede_id', $user->sede_id) == $sede->id)>{{ $sede->nombre }}</option>
                    @endforeach
                </select>
            </template>
            <p x-show="rol === 'VIGILANTE'" x-cloak class="text-xs text-gray-400 mt-1">
                Obligatoria: define en qué puerta puede confirmar salidas/retornos.
            </p>
            <x-input-error :messages="$errors->get('sede_id')" />
        </div>
        <div x-show="rol === 'TRABAJADOR'" x-cloak>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Jefe inmediato</label>
            <select name="jefe_id" class="input-glass"
                    @change="sedeJefe = $event.target.selectedOptions[0]?.dataset.sede || ''">
                <option value="">Selecciona un jefe</option>
                @foreach($jefes as $jefe)
                    <option value="{{ $jefe->id }}" data-sede="{{ $jefe->sede?->nombre ?? '' }}" @selected(old('jefe_id', $user->jefe_id) == $jefe->id)>{{ $jefe->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">
                La sede del trabajador se asigna automáticamente: siempre es la misma que la del jefe elegido.
            </p>
            <x-input-error :messages="$errors->get('jefe_id')" />
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="estado" value="0">
            <input type="checkbox" name="estado" value="1" id="estado" class="rounded border-gray-300 text-brand-600 focus:ring-brand-400" @checked(old('estado', $user->estado))>
            <label for="estado" class="text-sm text-gray-700">Activo</label>
        </div>

        <div class="flex gap-3 pt-1">
            <button type="submit" class="btn-primary">Actualizar</button>
            <a href="{{ route('users.index') }}" class="text-gray-500 hover:text-gray-800 px-4 py-2 text-sm font-medium transition-colors">Cancelar</a>
        </div>
    </form>
</x-app-layout>
