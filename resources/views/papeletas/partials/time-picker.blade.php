{{--
    Selector de hora. En móvil: 3 selects en formato 12h con AM/PM explícito.

    Reemplaza al <input type="time"> nativo EN MÓVIL: ese input depende del
    idioma / configuración regional del navegador para decidir si muestra
    12h o 24h, y su atributo `min` fue la causa de un bug donde, de noche,
    se bloqueaba elegir una hora normal del día siguiente (ver
    create.blade.php para el detalle del bug de zona horaria). Con selects
    propios el formato es siempre el mismo para todos los usuarios y no hay
    ningún `min` nativo que pueda bloquear una hora válida.

    En escritorio (sm: y superior) se muestra en cambio un <input
    type="time"> nativo — más rápido con teclado que tres selects — SIN
    atributo `min` (para no reintroducir el bug de zona horaria de arriba).
    Su valor ya viene garantizado en formato 24h "HH:mm" por el estándar
    HTML5 sin importar cómo lo despliegue el navegador, así que no necesita
    conversión: se escribe tal cual al mismo input hidden que usan los
    selects.

    El valor final en formato 24h "H:i" (el que espera el backend, sin
    cambios) se escribe en un <input type="hidden"> con el `name` real del
    campo. Ni los tres selects ni el input nativo tienen `name` — son solo
    UI, ambos alimentan el mismo hidden.

    Props esperadas:
    - name: name del input hidden que se envía al servidor.
    - id: id del input hidden (y prefijo del contenedor, "picker-{id}").
    - required: bool, si el campo es obligatorio (afecta a los selects
      visibles de hora/minuto, para que el navegador pida completarlos).
    - minutos: array de minutos disponibles, ej. [0, 5, 10, ..., 55].
--}}
@php
    $required = $required ?? false;
    $minutos = $minutos ?? range(0, 55, 5);
    $valorViejo = old($name);
@endphp

<div id="picker-{{ $id }}" class="grid grid-cols-3 gap-1.5 sm:hidden">
    <select data-role="hour" @if($required) required @endif
            class="input-glass !px-1 text-center" aria-label="Hora">
        <option value="">--</option>
        @for ($h = 1; $h <= 12; $h++)
            <option value="{{ $h }}">{{ $h }}</option>
        @endfor
    </select>

    <select data-role="minute" @if($required) required @endif
            class="input-glass !px-1 text-center" aria-label="Minuto">
        <option value="">--</option>
        @foreach ($minutos as $m)
            <option value="{{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}">
                {{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}
            </option>
        @endforeach
    </select>

    <select data-role="ampm" class="input-glass !px-1 text-center" aria-label="AM o PM">
        <option value="AM">AM</option>
        <option value="PM">PM</option>
    </select>
</div>

<input type="time" data-role="native" @if($required) required @endif
       class="input-glass hidden sm:block" aria-label="Hora">

<input type="hidden" name="{{ $name }}" id="{{ $id }}" value="{{ $valorViejo }}">
