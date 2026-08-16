@php
    $marcSalida = $papeleta->marcaciones->firstWhere('tipo', 'SALIDA');
    $marcRetorno = $papeleta->marcaciones->firstWhere('tipo', 'RETORNO');
    $horasFuera = null;

    if ($marcSalida) {
        $fin = $marcRetorno?->created_at ?? now();
        $horasFuera = round($marcSalida->created_at->diffInMinutes($fin) / 60, 1);
    }
@endphp
