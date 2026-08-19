<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 tracking-tight">{{ $esAdmin ? 'Trabajadores' : 'Mi equipo' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    @if($esAdmin)
                        Todos los trabajadores del sistema, agrupados por su jefe inmediato.
                    @else
                        Los trabajadores que reportan directamente a ti.
                    @endif
                </p>
            </div>
            <a href="{{ route('equipo.create') }}" class="btn-primary !px-4 !py-2">+ Nuevo trabajador</a>
        </div>
    </x-slot>

    <form method="GET" action="{{ route('equipo.index') }}"
          class="glass-card p-4 mb-4 grid grid-cols-1 {{ $esAdmin ? 'md:grid-cols-3' : 'md:grid-cols-2' }} gap-3 animate-fade-in-up">
        <input type="text" name="buscar" value="{{ $buscar }}"
               placeholder="Nombre o correo"
               class="input-glass !py-2 text-sm {{ $esAdmin ? '' : 'md:col-span-1' }}">

        @if($esAdmin)
            <select name="jefe_id" class="input-glass !py-2 text-sm">
                <option value="">Todos los jefes</option>
                @foreach($jefes as $jefe)
                    <option value="{{ $jefe->id }}" @selected((string) $jefeSeleccionado === (string) $jefe->id)>{{ $jefe->name }}</option>
                @endforeach
            </select>
        @endif

        <div class="flex justify-end gap-2">
            <a href="{{ route('equipo.index') }}" class="text-sm text-gray-500 hover:text-gray-800 px-3 py-2 font-medium transition-colors">Limpiar</a>
            <button type="submit" class="btn-secondary">Filtrar</button>
        </div>
    </form>

    <div class="glass-panel overflow-x-auto animate-fade-in-up">
        <table class="w-full text-sm">
            <thead class="text-left text-gray-500 text-xs uppercase tracking-wide border-b border-white/60">
                <tr>
                    <th class="p-3">Nombre</th>
                    <th class="p-3 hidden sm:table-cell">Email</th>
                    <th class="p-3 hidden md:table-cell">Cargo</th>
                    @if($esAdmin)
                        <th class="p-3 hidden sm:table-cell">Jefe</th>
                    @endif
                    <th class="p-3">Estado</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/50">
                @forelse($trabajadores as $trabajador)
                    <tr class="hover:bg-white/40 transition-colors">
                        <td class="p-3 font-medium text-gray-800">{{ $trabajador->name }}</td>
                        <td class="p-3 hidden sm:table-cell text-gray-500">{{ $trabajador->email }}</td>
                        <td class="p-3 hidden md:table-cell text-gray-500">{{ $trabajador->cargo?->nombre ?? '—' }}</td>
                        @if($esAdmin)
                            <td class="p-3 hidden sm:table-cell text-gray-500">{{ $trabajador->jefe?->name ?? '—' }}</td>
                        @endif
                        <td class="p-3">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $trabajador->estado ? 'bg-emerald-50/80 text-emerald-700 border border-emerald-200/60' : 'bg-gray-100/80 text-gray-500 border border-gray-200/60' }}">
                                {{ $trabajador->estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="p-3 text-right whitespace-nowrap">
                            <form method="POST" action="{{ route('equipo.destroy', $trabajador) }}" class="inline">
                                @csrf @method('DELETE')
                                <button
                                    class="font-medium {{ $trabajador->estado ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }}"
                                    onclick="return confirm('{{ $trabajador->estado ? '¿Desactivar' : '¿Activar' }} a {{ $trabajador->name }}?')"
                                >{{ $trabajador->estado ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $esAdmin ? 7 : 5 }}" class="p-10 text-center text-gray-400 text-sm">
                            @if($esAdmin)
                                No hay trabajadores que coincidan con el filtro.
                            @else
                                Todavía no tienes trabajadores registrados. Crea el primero con el botón de arriba.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $trabajadores->links() }}</div>
</x-app-layout>
