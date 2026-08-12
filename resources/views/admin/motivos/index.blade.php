<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Motivos</h2>
            <a href="{{ route('motivos.create') }}"
               class="btn-primary !px-4 !py-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo
            </a>
        </div>
    </x-slot>

    <x-live-refresh-banner tabla="motivos" :count="$motivos->total()" />

    <div class="glass-panel overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-gray-500 text-xs uppercase tracking-wide border-b border-white/60">
                <tr>
                    <th class="p-3">Nombre</th>
                    <th class="p-3 hidden sm:table-cell">Doc. requerido</th>
                    <th class="p-3 hidden sm:table-cell">Goce de haber</th>
                    <th class="p-3 hidden sm:table-cell">Máx. horas</th>
                    <th class="p-3">Estado</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/50">
                @forelse($motivos as $motivo)
                    <tr class="hover:bg-white/40 transition-colors">
                        <td class="p-3 font-medium text-gray-800">{{ $motivo->nombre }}</td>
                        <td class="p-3 hidden sm:table-cell">
                            @if($motivo->requiere_documento)
                                <span class="text-xs text-amber-600">📎 Sí</span>
                            @else
                                <span class="text-xs text-gray-400">No</span>
                            @endif
                        </td>
                        <td class="p-3 hidden sm:table-cell text-gray-500">{{ $motivo->goce_haber ? 'Sí' : 'No' }}</td>
                        <td class="p-3 hidden sm:table-cell text-gray-500">{{ $motivo->max_horas ?? '—' }}</td>
                        <td class="p-3"><x-active-badge :activo="$motivo->estado" /></td>
                        <td class="p-3 text-right space-x-3 whitespace-nowrap">
                            <a href="{{ route('motivos.edit', $motivo) }}" class="text-brand-600 hover:text-brand-800 font-semibold">Editar</a>
                            @if($motivo->estado)
                                <form method="POST" action="{{ route('motivos.destroy', $motivo) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-500 hover:text-rose-700 font-medium" onclick="return confirm('¿Desactivar este motivo?')">Desactivar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-10 text-center text-gray-400 text-sm">No hay motivos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $motivos->links() }}</div>
</x-app-layout>
