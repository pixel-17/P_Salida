@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 rounded-lg border-l-4 border-brand-400 text-start text-base font-semibold text-brand-700 bg-white/50 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 rounded-lg border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-white/40 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
