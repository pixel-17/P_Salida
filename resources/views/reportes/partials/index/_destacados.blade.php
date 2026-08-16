{{-- =========================================================
     DESTACADOS
========================================================== --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4 stagger">

    {{-- Trabajador con más salidas --}}
    <div class="glass-panel p-5 animate-fade-in-up">
        <p class="text-xs text-gray-500 mb-2">Trabajador que más solicitó</p>

        @if($rankingTrabajadores->isNotEmpty())
            @php $top = $rankingTrabajadores->first(); @endphp
            <p class="text-lg font-extrabold text-gray-800">{{ $top['nombre'] }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $top['area'] }} · Jefe: {{ $top['jefe'] }}</p>
            <p class="text-sm font-bold text-brand-700 mt-2">{{ $top['total'] }} salidas</p>
        @else
            <p class="text-sm text-gray-400">Sin datos en este rango.</p>
        @endif
    </div>

    {{-- Área más solicitada --}}
    <div class="glass-panel p-5 animate-fade-in-up">
        <p class="text-xs text-gray-500 mb-2">Área más solicitada</p>

        @if($rankingAreas->isNotEmpty())
            @php $topArea = $rankingAreas->first(); @endphp
            <p class="text-lg font-extrabold text-gray-800">{{ $topArea['nombre'] }}</p>
            <p class="text-sm font-bold text-purple-700 mt-2">{{ $topArea['total'] }} salidas</p>
        @else
            <p class="text-sm text-gray-400">Sin datos en este rango.</p>
        @endif
    </div>

    {{-- Más horas fuera --}}
    <div class="glass-panel p-5 animate-fade-in-up">
        <p class="text-xs text-gray-500 mb-2">Trabajador con más horas afuera</p>

        @if($rankingHorasFuera->isNotEmpty())
            @php $topHoras = $rankingHorasFuera->first(); @endphp
            <p class="text-lg font-extrabold text-gray-800">{{ $topHoras['nombre'] }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $topHoras['area'] }} · Jefe: {{ $topHoras['jefe'] }}</p>
            <p class="text-sm font-bold text-emerald-700 mt-2">{{ number_format($topHoras['horas'], 1) }}h fuera</p>
        @else
            <p class="text-sm text-gray-400">Sin datos en este rango.</p>
        @endif
    </div>
</div>
