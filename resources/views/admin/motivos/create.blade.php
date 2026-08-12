<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Nuevo Motivo</h2>
    </x-slot>

    <form method="POST" action="{{ route('motivos.store') }}" class="glass-panel p-6 space-y-4 max-w-lg animate-fade-in-up">
        @csrf
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Nombre</label>
            <input type="text" name="nombre" required value="{{ old('nombre') }}" class="input-glass">
        </div>
        <div>
            <label class="block font-semibold text-sm text-gray-600 mb-1.5">Máx. horas (opcional)</label>
            <input type="number" name="max_horas" value="{{ old('max_horas') }}" class="input-glass">
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="requiere_documento" value="1" id="requiere_documento" class="rounded" @checked(old('requiere_documento'))>
            <label for="requiere_documento" class="text-sm text-gray-700">Requiere documento sustentatorio</label>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="goce_haber" value="1" id="goce_haber" class="rounded" @checked(old('goce_haber'))>
            <label for="goce_haber" class="text-sm text-gray-700">Con goce de haber</label>
        </div>
        <div class="flex gap-3 pt-1">
            <button type="submit" class="btn-primary">Guardar</button>
            <a href="{{ route('motivos.index') }}" class="text-gray-500 hover:text-gray-800 px-4 py-2.5 text-sm font-medium transition-colors">Cancelar</a>
        </div>
    </form>
</x-app-layout>
