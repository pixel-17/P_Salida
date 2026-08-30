<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Editar Sede</h2>
    </x-slot>

    <form method="POST" action="{{ route('sedes.update', $sede) }}" class="glass-panel p-6 space-y-4 max-w-lg animate-fade-in-up">
        @csrf @method('PUT')
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Nombre</label>
            <input type="text" name="nombre" required value="{{ old('nombre', $sede->nombre) }}" class="input-glass">
            <x-input-error :messages="$errors->get('nombre')" />
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Dirección</label>
            <input type="text" name="direccion" value="{{ old('direccion', $sede->direccion) }}" class="input-glass">
            <x-input-error :messages="$errors->get('direccion')" />
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block font-semibold text-sm text-gray-600 mb-1.5">Latitud</label>
                <input type="number" step="any" name="latitud" required value="{{ old('latitud', $sede->latitud) }}" class="input-glass">
                <x-input-error :messages="$errors->get('latitud')" />
            </div>
            <div>
                <label class="block font-semibold text-sm text-gray-600 mb-1.5">Longitud</label>
                <input type="number" step="any" name="longitud" required value="{{ old('longitud', $sede->longitud) }}" class="input-glass">
                <x-input-error :messages="$errors->get('longitud')" />
            </div>
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="estado" value="0">
            <input type="checkbox" name="estado" value="1" id="estado" class="rounded border-gray-300 text-brand-600 focus:ring-brand-400" @checked(old('estado', $sede->estado))>
            <label for="estado" class="text-sm text-gray-700">Activa</label>
        </div>
        <div class="flex gap-3 pt-1">
            <button type="submit" class="btn-primary">Actualizar</button>
            <a href="{{ route('sedes.index') }}" class="text-gray-500 hover:text-gray-800 px-4 py-2.5 text-sm font-medium transition-colors">Cancelar</a>
        </div>
    </form>
</x-app-layout>
