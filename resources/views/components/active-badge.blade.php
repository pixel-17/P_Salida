@props(['activo', 'textoActivo' => 'Activo', 'textoInactivo' => 'Inactivo'])

<span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full backdrop-blur-sm shadow-sm {{ $activo ? 'bg-emerald-50/80 text-emerald-700 border border-emerald-200/60' : 'bg-gray-100/80 text-gray-500 border border-gray-200/60' }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $activo ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
    {{ $activo ? $textoActivo : $textoInactivo }}
</span>
