<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Editar trabajador</h2>
    </x-slot>

    <form method="POST" action="{{ route('equipo.update', $trabajador) }}" class="glass-panel p-6 space-y-4 max-w-lg animate-fade-in-up">
        @csrf @method('PUT')

        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Nombre completo</label>
            <input type="text" name="name" required value="{{ old('name', $trabajador->name) }}" class="input-glass">
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Email</label>
            <input type="email" name="email" required value="{{ old('email', $trabajador->email) }}" class="input-glass">
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">DNI</label>
            <input type="text" name="dni" required maxlength="8" value="{{ old('dni', $trabajador->dni) }}" class="input-glass">
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Teléfono</label>
            <input type="text" name="telefono" value="{{ old('telefono', $trabajador->telefono) }}" class="input-glass">
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Nueva contraseña</label>
            <input type="password" name="password" class="input-glass">
            <p class="text-xs text-gray-400 mt-1">Déjalo en blanco para mantener la actual. Si la cambias, se le pedirá volver a definirla al iniciar sesión.</p>
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Cargo</label>
            <select name="cargo_id" class="input-glass">
                <option value="">Sin asignar</option>
                @foreach($cargos as $cargo)
                    <option value="{{ $cargo->id }}" @selected(old('cargo_id', $trabajador->cargo_id) == $cargo->id)>{{ $cargo->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Jefe inmediato</label>
            <select name="jefe_id" required class="input-glass">
                @foreach($jefes as $jefe)
                    <option value="{{ $jefe->id }}" @selected(old('jefe_id', $trabajador->jefe_id) == $jefe->id)>{{ $jefe->name }} — {{ $jefe->sede?->nombre ?? 'sin sede' }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">
                La sede del trabajador se recalcula sola: siempre queda igual a la del jefe elegido
                (sede actual: {{ $trabajador->sede?->nombre ?? 'sin asignar' }}).
            </p>
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="estado" value="0">
            <input type="checkbox" id="estado" name="estado" value="1" @checked(old('estado', $trabajador->estado)) class="rounded border-gray-300 text-brand-600">
            <label for="estado" class="text-sm text-gray-600">Trabajador activo</label>
        </div>

        <div class="flex gap-3 pt-1">
            <button type="submit" class="btn-primary">Guardar cambios</button>
            <a href="{{ route('equipo.index') }}" class="text-gray-500 hover:text-gray-800 px-4 py-2 text-sm font-medium transition-colors">Cancelar</a>
        </div>
    </form>
</x-app-layout>
