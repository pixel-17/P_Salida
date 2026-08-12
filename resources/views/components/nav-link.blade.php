@props(['active'])

@php
$classes = ($active ?? false)
            ? 'relative inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold text-brand-700 bg-white/60 transition duration-150 ease-in-out'
            : 'relative inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-800 hover:bg-white/40 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
