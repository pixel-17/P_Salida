<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Usuarios</h2>
            <a href="{{ route('users.create') }}" class="btn-primary !px-4 !py-2">+ Nuevo</a>
        </div>
    </x-slot>

    <form method="GET" action="{{ route('users.index') }}"
          class="glass-card p-4 mb-4 grid grid-cols-1 md:grid-cols-5 gap-3 animate-fade-in-up">
        <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}"
               placeholder="Nombre o correo"
               class="input-glass !py-2 text-sm col-span-1 md:col-span-2">

        <select name="rol" class="input-glass !py-2 text-sm">
            <option value="">Todos los roles</option>
            @foreach ($roles as $rol)
                <option value="{{ $rol->value }}" @selected(($filtros['rol'] ?? null) === $rol->value)>
                    {{ $rol->label() }}
                </option>
            @endforeach
        </select>

        <select name="area_id" class="input-glass !py-2 text-sm">
            <option value="">Todas las áreas</option>
            @foreach ($areas as $area)
                <option value="{{ $area->id }}" @selected(($filtros['area_id'] ?? null) == $area->id)>
                    {{ $area->nombre }}
                </option>
            @endforeach
        </select>

        <select name="sede_id" class="input-glass !py-2 text-sm">
            <option value="">Todas las sedes</option>
            @foreach ($sedes as $sede)
                <option value="{{ $sede->id }}" @selected(($filtros['sede_id'] ?? null) == $sede->id)>
                    {{ $sede->nombre }}
                </option>
            @endforeach
        </select>

        <select name="estado" class="input-glass !py-2 text-sm">
            <option value="">Activos e inactivos</option>
            <option value="activo" @selected(($filtros['estado'] ?? null) === 'activo')>Solo activos</option>
            <option value="inactivo" @selected(($filtros['estado'] ?? null) === 'inactivo')>Solo inactivos</option>
        </select>

        <div class="col-span-1 md:col-span-5 flex justify-end gap-2 pt-1">
            <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-800 px-3 py-2 font-medium transition-colors">Limpiar</a>
            <button type="submit" class="btn-secondary">Filtrar</button>
        </div>
    </form>

    <div class="glass-panel overflow-x-auto animate-fade-in-up">
        <table class="w-full text-sm">
            <thead class="text-left text-gray-500 text-xs uppercase tracking-wide border-b border-white/60">
                <tr>
                    <th class="p-3">Nombre</th>
                    <th class="p-3 hidden sm:table-cell">Email</th>
                    <th class="p-3">Rol</th>
                    <th class="p-3 hidden sm:table-cell">Jefe</th>
                    <th class="p-3">Estado</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/50">
                @forelse($users as $user)
                    <tr class="hover:bg-white/40 transition-colors">
                        <td class="p-3 font-medium text-gray-800">{{ $user->name }}</td>
                        <td class="p-3 hidden sm:table-cell text-gray-500">{{ $user->email }}</td>
                        <td class="p-3">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-brand-50/80 text-brand-700 border border-brand-100">
                                {{ $user->getRoleNames()->first() ?? '—' }}
                            </span>
                        </td>
                        <td class="p-3 hidden sm:table-cell text-gray-500">{{ $user->jefe?->name ?? '—' }}</td>
                        <td class="p-3">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $user->estado ? 'bg-emerald-50/80 text-emerald-700 border border-emerald-200/60' : 'bg-gray-100/80 text-gray-500 border border-gray-200/60' }}">
                                {{ $user->estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="p-3 text-right space-x-3 whitespace-nowrap">
                            <a href="{{ route('users.edit', $user) }}" class="text-brand-600 hover:text-brand-800 font-semibold">Editar</a>
                            @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-500 hover:text-rose-700 font-medium" onclick="return confirm('¿Desactivar este usuario?')">Desactivar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-10 text-center text-gray-400 text-sm">
                        @if(array_filter($filtros))
                            No hay usuarios que coincidan con el filtro.
                        @else
                            No hay usuarios registrados.
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-app-layout>
