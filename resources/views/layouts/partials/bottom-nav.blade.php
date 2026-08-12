{{--
    Barra inferior de navegación, pensada para móvil.
    Recibe $items: [['label','route','active','icon','badge' => ?int]]
--}}
<nav class="bottom-nav">
    @foreach ($items as $item)
        <a href="{{ $item['route'] }}" class="bottom-nav-item {{ request()->routeIs(...(array) $item['active']) ? 'is-active' : '' }}">
            <x-icon :name="$item['icon']" />
            @if(!empty($item['badge']))
                <span class="nav-badge">{{ $item['badge'] > 9 ? '9+' : $item['badge'] }}</span>
            @endif
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
