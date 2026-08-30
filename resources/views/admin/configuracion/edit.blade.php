<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Configuración general</h2>
    </x-slot>

    <form method="POST" action="{{ route('configuracion.update') }}" class="glass-panel p-6 space-y-5 max-w-lg animate-fade-in-up">
        @csrf @method('PUT')

        <div>
            <h3 class="font-semibold text-gray-700 mb-3">Horario laboral</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-sm text-gray-600 mb-1.5">Inicio de jornada</label>
                    <input type="time" name="horario_laboral_inicio" required
                        value="{{ old('horario_laboral_inicio', $horarioInicio) }}" class="input-glass">
                    <x-input-error :messages="$errors->get('horario_laboral_inicio')" />
                </div>
                <div>
                    <label class="block font-semibold text-sm text-gray-600 mb-1.5">Fin de jornada</label>
                    <input type="time" name="horario_laboral_fin" required
                        value="{{ old('horario_laboral_fin', $horarioFin) }}" class="input-glass">
                    <x-input-error :messages="$errors->get('horario_laboral_fin')" />
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-1.5">Solo se podrán registrar salidas programadas dentro de este rango. El vigilante podrá confirmar salidas y retornos en garita hasta 10 minutos después del fin de jornada.</p>
        </div>

        <div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="domingo_laborable" value="0">
                <input type="checkbox" name="domingo_laborable" value="1" id="domingo_laborable"
                    class="rounded border-gray-300 text-brand-600 focus:ring-brand-400"
                    @checked(old('domingo_laborable', $domingoLaborable))>
                <label for="domingo_laborable" class="text-sm text-gray-700">Se labora los domingos</label>
            </div>
            <p class="text-xs text-gray-500 mt-1.5">Si está desmarcado, no se podrán crear papeletas ni registrar en garita los domingos.</p>
        </div>

        <div class="flex gap-3 pt-1">
            <button type="submit" class="btn-primary">Guardar cambios</button>
        </div>
    </form>
</x-app-layout>
