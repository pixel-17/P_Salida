{{-- =========================================================
     NAV DE PESTAÑAS
     Estado "tab" vive en el x-data del contenedor padre
     (reportes/index.blade.php), así lo comparten esta barra,
     los paneles y el hidden del formulario de filtros.
========================================================== --}}
<div class="glass-card p-1.5 mb-4 inline-flex flex-wrap gap-1 animate-fade-in-up" role="tablist">
    <template x-for="opcion in [
        { id: 'resumen', label: 'Resumen', icono: '📊' },
        { id: 'trabajador', label: 'Por trabajador', icono: '🏆' },
        { id: 'area_motivo', label: 'Área y motivo', icono: '🗂️' },
        { id: 'detalle', label: 'Detalle', icono: '📋' },
    ]" :key="opcion.id">
        <button type="button"
                role="tab"
                :aria-selected="tab === opcion.id"
                @click="tab = opcion.id"
                class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors inline-flex items-center gap-1.5"
                :class="tab === opcion.id
                    ? 'bg-brand-600 text-white shadow-glass'
                    : 'text-gray-500 hover:bg-gray-50'">
            <span x-text="opcion.icono"></span>
            <span x-text="opcion.label"></span>
        </button>
    </template>
</div>
