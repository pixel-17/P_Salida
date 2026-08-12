<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Panel de Administración</h2>
    </x-slot>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4 stagger">
        <div class="glass-card p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">👤</div>
            <div>
                <p class="text-2xl font-extrabold text-gray-800">{{ $totalUsuarios }}</p>
                <p class="text-xs text-gray-500">Usuarios</p>
            </div>
        </div>
        <div class="glass-card p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">🏢</div>
            <div>
                <p class="text-2xl font-extrabold text-gray-800">{{ $totalAreas }}</p>
                <p class="text-xs text-gray-500">Áreas</p>
            </div>
        </div>
        <div class="glass-card p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">📍</div>
            <div>
                <p class="text-2xl font-extrabold text-gray-800">{{ $totalSedes }}</p>
                <p class="text-xs text-gray-500">Sedes</p>
            </div>
        </div>
    </div>

    <div class="glass-panel p-5 mb-4 animate-fade-in-up">
        <h3 class="font-semibold text-sm text-gray-700 mb-4">Papeletas por estado</h3>
        @forelse($papeletasPorEstado as $fila)
            <div class="flex items-center gap-3 py-2 border-b border-white/50 last:border-0">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $fila->color }}"></span>
                <span class="text-sm text-gray-700 flex-1">{{ $fila->nombre }}</span>
                <span class="text-sm font-bold text-gray-800">{{ $fila->total }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-400">Aún no hay papeletas registradas.</p>
        @endforelse
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 stagger">
        <a href="{{ route('users.index') }}" class="glass-card p-4 text-center">
            <div class="text-2xl mb-1">👤</div>
            <span class="text-sm text-gray-700 font-semibold">Usuarios</span>
        </a>
        <a href="{{ route('areas.index') }}" class="glass-card p-4 text-center">
            <div class="text-2xl mb-1">🏢</div>
            <span class="text-sm text-gray-700 font-semibold">Áreas</span>
        </a>
        <a href="{{ route('cargos.index') }}" class="glass-card p-4 text-center">
            <div class="text-2xl mb-1">🧑‍💼</div>
            <span class="text-sm text-gray-700 font-semibold">Cargos</span>
        </a>
        <a href="{{ route('sedes.index') }}" class="glass-card p-4 text-center">
            <div class="text-2xl mb-1">📍</div>
            <span class="text-sm text-gray-700 font-semibold">Sedes</span>
        </a>
        <a href="{{ route('motivos.index') }}" class="glass-card p-4 text-center">
            <div class="text-2xl mb-1">📋</div>
            <span class="text-sm text-gray-700 font-semibold">Motivos</span>
        </a>
        <a href="{{ route('papeletas.index') }}" class="glass-card p-4 text-center">
            <div class="text-2xl mb-1">📄</div>
            <span class="text-sm text-gray-700 font-semibold">Papeletas</span>
        </a>
    </div>
</x-app-layout>
