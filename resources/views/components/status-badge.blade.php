@props(['estado'])

<span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full whitespace-nowrap backdrop-blur-sm shadow-sm animate-fade-in"
      style="background-color: {{ $estado->color }}1f; color: {{ $estado->color }}; border: 1px solid {{ $estado->color }}33;">
    <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $estado->color }}"></span>
    {{ $estado->nombre }}
</span>
