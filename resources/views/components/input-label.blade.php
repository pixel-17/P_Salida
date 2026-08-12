@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-gray-600 mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
