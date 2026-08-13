<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de papeletas</title>
    <style>
        @page { margin: 26px 30px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 9.5px; }
        .header {
            display: table; width: 100%;
            border-bottom: 2px solid #16a34a;
            padding-bottom: 10px; margin-bottom: 12px;
        }
        .header .col { display: table-cell; vertical-align: middle; }
        .header .col.right { text-align: right; font-size: 9px; color: #6b7280; }
        .titulo { font-size: 16px; font-weight: bold; color: #15803d; margin: 0; }
        .subtitulo { font-size: 9.5px; color: #6b7280; margin: 2px 0 0; }

        .kpis { display: table; width: 100%; margin-bottom: 14px; }
        .kpi {
            display: table-cell;
            width: 25%;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 8px 10px;
            text-align: center;
        }
        .kpi + .kpi { border-left: none; }
        .kpi .valor { font-size: 16px; font-weight: bold; color: #15803d; }
        .kpi .etiqueta { font-size: 8px; color: #6b7280; text-transform: uppercase; margin-top: 2px; }

        table.detalle { width: 100%; border-collapse: collapse; }
        table.detalle th, table.detalle td {
            border: 1px solid #e5e7eb;
            padding: 4px 6px;
            font-size: 8.5px;
            text-align: left;
        }
        table.detalle th { background: #f0fdf4; color: #166534; }
        table.detalle tr:nth-child(even) { background: #fafafa; }
        .footer { margin-top: 14px; font-size: 8px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="col">
            <p class="titulo">{{ config('app.name') }}</p>
            <p class="subtitulo">Reporte gerencial de papeletas de salida</p>
        </div>
        <div class="col right">
            Generado: {{ now()->format('d/m/Y h:i A') }}<br>
            Por: {{ auth()->user()->name }}<br>
            @if(!empty($filtrosLegibles))
                Filtros: {{ implode(' · ', $filtrosLegibles) }}
            @else
                Sin filtros aplicados
            @endif
        </div>
    </div>

    <div class="kpis">
        <div class="kpi">
            <div class="valor">{{ $kpis['total'] }}</div>
            <div class="etiqueta">Total papeletas</div>
        </div>
        <div class="kpi">
            <div class="valor">{{ $kpis['finalizadas'] }}</div>
            <div class="etiqueta">Finalizadas</div>
        </div>
        <div class="kpi">
            <div class="valor">{{ $kpis['rechazadas_vencidas'] }}</div>
            <div class="etiqueta">Rechazadas / Vencidas</div>
        </div>
        <div class="kpi">
            <div class="valor">{{ $kpis['tiempo_promedio_aprobacion'] }}</div>
            <div class="etiqueta">Tiempo prom. de aprobación</div>
        </div>
    </div>

    <table class="detalle">
        <thead>
            <tr>
                <th>Código</th>
                <th>Trabajador</th>
                <th>Área</th>
                <th>Motivo</th>
                <th>Fecha salida</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($papeletas as $papeleta)
                <tr>
                    <td>{{ $papeleta->codigo }}</td>
                    <td>{{ $papeleta->trabajador->name }}</td>
                    <td>{{ $papeleta->area?->nombre ?? '—' }}</td>
                    <td>{{ $papeleta->motivo->nombre }}</td>
                    <td>{{ $papeleta->fecha_salida->format('d/m/Y') }}</td>
                    <td>{{ $papeleta->estado->nombre }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No hay papeletas con los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ config('app.name') }} &middot; Reporte de uso interno &middot; {{ $papeletas->count() }} registro(s) listado(s)
    </div>
</body>
</html>
