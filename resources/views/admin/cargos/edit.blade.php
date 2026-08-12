<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Editar Cargo</h2>
    </x-slot>

    <form method="POST" action="{{ route('cargos.update', $cargo) }}" class="glass-panel p-6 space-y-4 max-w-lg animate-fade-in-up">
        @csrf @method('PUT')
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Área</label>
            <select name="area_id" required class="input-glass">
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" @selected(old('area_id', $cargo->area_id) == $area->id)>{{ $area->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Nombre</label>
            <input type="text" name="nombre" required value="{{ old('nombre', $cargo->nombre) }}" class="input-glass">
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="estado" value="0">
            <input type="checkbox" name="estado" value="1" id="estado" class="rounded border-gray-300 text-brand-600 focus:ring-brand-400" @checked(old('estado', $cargo->estado))>
            <label for="estado" class="text-sm text-gray-700">Activo</label>
        </div>
        <div class="flex gap-3 pt-1">
            <button type="submit" class="btn-primary">Actualizar</button>
            <a href="{{ route('cargos.index') }}" class="text-gray-500 hover:text-gray-800 px-4 py-2.5 text-sm font-medium transition-colors">Cancelar</a>
        </div>
    </form>
</x-app-layout>
