<div class="glass-card p-5 animate-fade-in-up">
    <h2 class="font-semibold text-sm text-gray-700 mb-2">Historial</h2>
    <ul class="text-xs text-gray-600 space-y-2">
        @foreach($papeleta->historial as $item)
            <li class="border-b border-white/60 pb-2 last:border-0">
                <span class="font-medium text-gray-700">{{ $item->accion }}</span>
                — {{ $item->usuario?->name ?? 'Sistema' }}
                <span class="text-gray-400">({{ $item->created_at->diffForHumans() }})</span>
            </li>
        @endforeach
    </ul>
</div>
