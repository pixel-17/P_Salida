<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Nuevo trabajador</h2>
    </x-slot>

    <form method="POST" action="{{ route('equipo.store') }}" class="glass-panel p-6 space-y-4 max-w-lg animate-fade-in-up">
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
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Contraseña</label>
            <input type="password" name="password" required class="input-glass">
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Cargo</label>
            <select name="cargo_id" class="input-glass">
                <option value="">Sin asignar</option>
                @foreach($cargos as $cargo)
                    <option value="{{ $cargo->id }}" @selected(old('cargo_id') == $cargo->id)>{{ $cargo->nombre }}</option>
                @endforeach
            </select>
        </div>

        @if($esAdmin)
            <div>
                <label class="block font-semibold text-sm text-gray-600 mb-1.5">Jefe inmediato</label>
                <select name="jefe_id" required class="input-glass">
                    <option value="">Selecciona un jefe</option>
                    @foreach($jefes as $jefe)
                        <option value="{{ $jefe->id }}" @selected(old('jefe_id') == $jefe->id)>{{ $jefe->name }} — {{ $jefe->sede?->nombre ?? 'sin sede' }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">
                    La sede del trabajador se asigna automáticamente: siempre es la misma que la del jefe elegido.
                </p>
            </div>
        @else
            <p class="text-xs text-gray-400">
                Este trabajador quedará asignado automáticamente a ti como jefe inmediato, en tu misma sede
                @if($miSede)
                    ({{ $miSede->nombre }}).
                @else
                    (todavía no tienes una sede asignada — pídele a un administrador que te la asigne primero).
                @endif
            </p>
        @endif

        <div class="flex gap-3 pt-1">
            <button type="submit" class="btn-primary">Guardar</button>
            <a href="{{ route('equipo.index') }}" class="text-gray-500 hover:text-gray-800 px-4 py-2 text-sm font-medium transition-colors">Cancelar</a>
        </div>
    </form>
</x-app-layout>
