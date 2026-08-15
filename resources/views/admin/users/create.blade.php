<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Nuevo Usuario</h2>
    </x-slot>

    <form x-data="{ rol: '{{ old('rol') }}' }" method="POST" action="{{ route('users.store') }}" class="glass-panel p-6 space-y-4 max-w-lg animate-fade-in-up">
        @csrf

        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Nombre completo</label>
            <input type="text" name="name" required value="{{ old('name') }}" class="input-glass">
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Email</label>
            <input type="email" name="email" required value="{{ old('email') }}" class="input-glass">
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">DNI</label>
            <input type="text" name="dni" required maxlength="8" value="{{ old('dni') }}" class="input-glass">
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Teléfono</label>
            <input type="text" name="telefono" value="{{ old('telefono') }}" class="input-glass">
        </div>
        <div class="rounded-lg bg-brand-50 border border-brand-100 px-3 py-2.5 text-xs text-brand-700">
            La contraseña inicial será su número de DNI. Se le pedirá cambiarla al iniciar sesión por primera vez.
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Rol</label>
            <select name="rol" x-model="rol" required class="input-glass">
                <option value="">Selecciona un rol</option>
                @foreach(\App\Enums\RolUsuario::cases() as $rol)
                    <option value="{{ $rol->value }}" @selected(old('rol') === $rol->value)>{{ $rol->label() }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="rol !== 'VIGILANTE'" x-cloak>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Cargo</label>
            <select name="cargo_id" class="input-glass">
                <option value="">Sin asignar</option>
                @foreach($cargos as $cargo)
                    <option value="{{ $cargo->id }}" @selected(old('cargo_id') == $cargo->id)>{{ $cargo->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="!['RRHH', 'ADMINISTRADOR'].includes(rol)" x-cloak>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">
                Sede <span x-show="rol === 'VIGILANTE'" x-cloak class="text-red-500">*</span>
            </label>
            <select name="sede_id" class="input-glass">
                <option value="">Sin asignar</option>
                @foreach($sedes as $sede)
                    <option value="{{ $sede->id }}" @selected(old('sede_id') == $sede->id)>{{ $sede->nombre }}</option>
                @endforeach
            </select>
            <p x-show="rol === 'VIGILANTE'" x-cloak class="text-xs text-gray-400 mt-1">
                Obligatoria: define en qué puerta puede confirmar salidas/retornos.
            </p>
        </div>
        <div x-show="rol === 'TRABAJADOR'" x-cloak>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Jefe inmediato</label>
            <select name="jefe_id" class="input-glass">
                <option value="">Sin asignar</option>
                @foreach($jefes as $jefe)
                    <option value="{{ $jefe->id }}" @selected(old('jefe_id') == $jefe->id)>{{ $jefe->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-3 pt-1">
            <button type="submit" class="btn-primary">Guardar</button>
            <a href="{{ route('users.index') }}" class="text-gray-500 hover:text-gray-800 px-4 py-2 text-sm font-medium transition-colors">Cancelar</a>
        </div>
    </form>
</x-app-layout>
