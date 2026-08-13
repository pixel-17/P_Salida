<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Bitácora de actividad</h2>
            <p class="text-sm text-gray-500 mt-0.5">Cambios sobre usuarios y catálogos (áreas, cargos, sedes, motivos).</p>
        </div>
    </x-slot>

    @php
        $coloresAccion = [
            'CREAR' => '#16a34a',
            'ACTUALIZAR' => '#2563eb',
            'DESACTIVAR' => '#d97706',
            'REACTIVAR' => '#16a34a',
            'ELIMINAR' => '#dc2626',
        ];
    @endphp

    <form method="GET" class="glass-panel p-4 mb-4 grid grid-cols-2 sm:grid-cols-5 gap-3">
        <select name="tipo" class="rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
            <option value="">Todos los recursos</option>
            @foreach($tipos as $tipo)
                <option value="{{ $tipo }}" @selected(($filtros['tipo'] ?? null) === $tipo)>{{ $tipo }}</option>
            @endforeach
        </select>

        <select name="accion" class="rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
            <option value="">Toda acción</option>
            @foreach(['CREAR', 'ACTUALIZAR', 'DESACTIVAR', 'REACTIVAR', 'ELIMINAR'] as $accion)
                <option value="{{ $accion }}" @selected(($filtros['accion'] ?? null) === $accion)>{{ ucfirst(strtolower($accion)) }}</option>
            @endforeach
        </select>

        <select name="usuario_id" class="rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
            <option value="">Todo usuario</option>
            @foreach($usuariosConActividad as $usuario)
                <option value="{{ $usuario->id }}" @selected((string) ($filtros['usuario_id'] ?? '') === (string) $usuario->id)>{{ $usuario->name }}</option>
            @endforeach
        </select>

        <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
        <input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
    </form>

    <div class="glass-panel overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-gray-500 text-xs uppercase tracking-wide border-b border-white/60">
                <tr>
                    <th class="p-3">Fecha</th>
                    <th class="p-3">Usuario</th>
                    <th class="p-3">Acción</th>
                    <th class="p-3">Recurso</th>
                    <th class="p-3 hidden md:table-cell">Cambios</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/50">
                @forelse($logs as $log)
                    <tr class="hover:bg-white/40 transition-colors align-top">
                        <td class="p-3 text-gray-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y h:i A') }}</td>
                        <td class="p-3 font-medium text-gray-800">{{ $log->usuario?->name ?? 'Sistema' }}</td>
                        <td class="p-3">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap"
                                  style="background-color: {{ $coloresAccion[$log->accion] ?? '#6b7280' }}1f; color: {{ $coloresAccion[$log->accion] ?? '#6b7280' }};">
                                {{ ucfirst(strtolower($log->accion)) }}
                            </span>
                        </td>
                        <td class="p-3 text-gray-700">
                            {{ $log->tipoLegible() }}
                            @if($log->auditable_label)
                                <span class="text-gray-400">&middot; {{ $log->auditable_label }}</span>
                            @endif
                        </td>
                        <td class="p-3 hidden md:table-cell text-xs text-gray-500">
                            @if($log->cambios)
                                <ul class="space-y-0.5">
                                    @foreach($log->cambios as $campo => $valores)
                                        <li><span class="font-semibold text-gray-600">{{ $campo }}:</span>
                                            {{ is_bool($valores[0]) ? ($valores[0] ? 'Sí' : 'No') : ($valores[0] ?? '—') }}
                                            → {{ is_bool($valores[1]) ? ($valores[1] ? 'Sí' : 'No') : ($valores[1] ?? '—') }}
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-gray-400">Sin actividad registrada todavía.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</x-app-layout>
