<x-app-layout>

    @include('sustentos.partials._barra-filtros')

    <div class="glass-panel p-5 animate-fade-in-up">
        <h3 class="font-semibold text-sm text-gray-700 mb-4">
            Papeletas con documento requerido
            <span class="text-xs font-normal text-gray-400">
                · {{ $sustentos->total() }} {{ Str::plural('resultado', $sustentos->total()) }}
            </span>
        </h3>

        @include('sustentos.partials._tabla-desktop')
        @include('sustentos.partials._cards-movil')

        <div class="mt-4">
            {{ $sustentos->links() }}
        </div>
    </div>

</x-app-layout>
