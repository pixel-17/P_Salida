{{-- =========================================================
     PESTAÑA: POR TRABAJADOR
     El cuadro dinámico (_cuadro-trabajadores) se conserva tal
     cual: mismas columnas, búsqueda y orden en cliente vía
     Alpine. Solo cambia dónde vive dentro de la pantalla.
========================================================== --}}
<div x-show="tab === 'trabajador'" x-cloak>
    @include('reportes.partials.index._cuadro-trabajadores')
</div>
