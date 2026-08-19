@php
    $marcSalida = $papeleta->marcaciones->firstWhere('tipo', 'SALIDA');
    $marcRetorno = $papeleta->marcaciones->firstWhere('tipo', 'RETORNO');
    $horasFuera = $papeleta->horasFuera();
@endphp
