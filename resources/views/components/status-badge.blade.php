@props(['estado', 'size' => 'sm', 'detalle' => null])

@php
    $clasesTamano = $size === 'lg'
        ? 'gap-2 text-base sm:text-lg font-extrabold px-5 py-2.5 rounded-2xl'
        : 'gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full';
    $puntoTamano = $size === 'lg' ? 'w-2.5 h-2.5' : 'w-1.5 h-1.5';

    // $estado->color son nombres CSS (gray, red, yellow...), no hex — por
    // eso no se puede pegarles un sufijo de alpha tipo "#rrggbb1f". Se usa
    // color-mix() (soportado en navegadores modernos) para aclarar/oscurecer
    // el mismo color con transparencia real, sin depender del formato.
    $fondo = "color-mix(in srgb, {$estado->color} 16%, white)";
    $texto = "color-mix(in srgb, {$estado->color} 65%, black)";
    $borde = "color-mix(in srgb, {$estado->color} 40%, white)";
@endphp

<span class="inline-flex items-center {{ $clasesTamano }} whitespace-nowrap backdrop-blur-sm shadow-sm animate-fade-in"
      style="background-color: {{ $fondo }}; color: {{ $texto }}; border: 1px solid {{ $borde }};">
    <span class="{{ $puntoTamano }} rounded-full shrink-0" style="background-color: {{ $estado->color }}"></span>
    <span>{{ $estado->nombre }}@if($detalle) — {{ $detalle }}@endif</span>
</span>
