<div class="glass-card p-5 animate-fade-in-up" x-data="{ abierto: false }">
    <button type="button" @click="abierto = !abierto"
            class="w-full flex items-center justify-between text-left"
            :aria-expanded="abierto.toString()">
        <h2 class="font-semibold text-sm text-gray-700">
            Historial <span class="text-gray-400 font-normal">({{ $papeleta->historial->count() }})</span>
        </h2>
        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0"
             :class="abierto ? 'rotate-180' : ''"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <ul x-show="abierto" x-cloak x-transition class="text-xs text-gray-600 space-y-2 mt-3">
        @foreach($papeleta->historial as $item)
            <li class="border-b border-white/60 pb-2 last:border-0">
                <span class="font-medium text-gray-700">{{ $item->accion }}</span>
                — {{ $item->usuario?->name ?? 'Sistema' }}
                <span class="text-gray-400">({{ $item->created_at->diffForHumans() }})</span>
            </li>
        @endforeach
    </ul>
</div>
