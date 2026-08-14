<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $papeleta->codigo }}</title>
    <style>
        @page { margin: 28px 34px; }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.5;
        }
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #16a34a;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .header .col { display: table-cell; vertical-align: middle; }
        .header .col.right { text-align: right; }
        .titulo { font-size: 18px; font-weight: bold; color: #15803d; margin: 0; }
        .subtitulo { font-size: 10px; color: #6b7280; margin: 2px 0 0; }
        .codigo-box {
            display: inline-block;
            padding: 6px 14px;
            border: 1.5px solid #16a34a;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            color: #15803d;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            color: #fff;
        }
        table.datos { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.datos td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }
        table.datos td.label {
            width: 28%;
            background: #f9fafb;
            font-weight: bold;
            color: #4b5563;
            font-size: 9.5px;
            text-transform: uppercase;
        }
        .seccion-titulo {
            font-size: 12px;
            font-weight: bold;
            color: #15803d;
            margin: 16px 0 6px;
            border-bottom: 1px solid #d1fae5;
            padding-bottom: 3px;
        }
        table.timeline { width: 100%; border-collapse: collapse; }
        table.timeline th, table.timeline td {
            border: 1px solid #e5e7eb;
            padding: 5px 7px;
            font-size: 9.5px;
            text-align: left;
        }
        table.timeline th { background: #f0fdf4; color: #166534; }
        .firma-box { display: table; width: 100%; margin-top: 34px; }
        .firma { display: table-cell; width: 33%; text-align: center; padding-top: 26px; }
        .firma .linea { border-top: 1px solid #9ca3af; margin: 0 14px; padding-top: 4px; font-size: 9.5px; color: #4b5563; }
        .footer { margin-top: 22px; font-size: 8.5px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="col">
            <p class="titulo">{{ config('app.name') }}</p>
            <p class="subtitulo">Boleta de Papeleta de Salida</p>
        </div>
        <div class="col right">
            <span class="codigo-box">{{ $papeleta->codigo }}</span><br>
            <span class="badge" style="background-color: {{ $papeleta->estado->color }}; margin-top: 6px;">
                {{ $papeleta->estado->nombre }}
            </span>
        </div>
    </div>

    <table class="datos">
        <tr>
            <td class="label">Trabajador</td>
            <td>{{ $papeleta->trabajador->name }}</td>
            <td class="label">Jefe / Aprobador</td>
            <td>{{ $papeleta->jefe?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Cargo</td>
            <td>{{ $papeleta->trabajador->cargo?->nombre ?? '—' }}</td>
            <td class="label">Área</td>
            <td>{{ $papeleta->area?->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Sede</td>
            <td>{{ $papeleta->sede?->nombre ?? '—' }}</td>
            <td class="label">Motivo</td>
            <td>{{ $papeleta->motivo->nombre }}</td>
        </tr>
        <tr>
            <td class="label">Destino</td>
            <td colspan="3">{{ $papeleta->destino }}</td>
        </tr>
        <tr>
            <td class="label">Fecha de salida</td>
            <td>{{ $papeleta->fecha_salida->format('d/m/Y') }}</td>
            <td class="label">Horario programado</td>
            <td>
                {{ \Illuminate\Support\Carbon::parse($papeleta->hora_salida_programada)->format('h:i A') }}
                @if($papeleta->hora_retorno_programada)
                    &ndash; {{ \Illuminate\Support\Carbon::parse($papeleta->hora_retorno_programada)->format('h:i A') }}
                @endif
            </td>
        </tr>
        @if($papeleta->motivo_detalle)
            <tr>
                <td class="label">Detalle</td>
                <td colspan="3">{{ $papeleta->motivo_detalle }}</td>
            </tr>
        @endif
    </table>

    <p class="seccion-titulo">Trazabilidad</p>
    <table class="timeline">
        <thead>
            <tr>
                <th style="width: 20%;">Fecha</th>
                <th style="width: 25%;">Acción</th>
                <th style="width: 30%;">Registrado por</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            @forelse($papeleta->historial->sortBy('created_at') as $evento)
                <tr>
                    <td>{{ $evento->created_at->format('d/m/Y h:i A') }}</td>
                    <td>{{ $evento->accion }}</td>
                    <td>{{ $evento->usuario?->name ?? 'Sistema' }}</td>
                    <td>{{ $evento->descripcion ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sin eventos registrados.</td></tr>
            @endforelse
            @foreach($papeleta->marcaciones as $marcacion)
                <tr>
                    <td>{{ $marcacion->created_at->format('d/m/Y h:i A') }}</td>
                    <td>Marcación de {{ strtolower($marcacion->tipo->value) }}</td>
                    <td>{{ $marcacion->registradoPor->name }}</td>
                    <td>Registrado en garita</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="firma-box">
        <div class="firma"><div class="linea">Firma del trabajador</div></div>
        <div class="firma"><div class="linea">Firma del jefe inmediato</div></div>
        <div class="firma"><div class="linea">V°B° RRHH</div></div>
    </div>

    <div class="footer">
        Documento generado el {{ now()->format('d/m/Y h:i A') }} por {{ auth()->user()->name }} &middot;
        {{ config('app.name') }} &middot; Este documento es un comprobante interno y no reemplaza registros oficiales de asistencia.
    </div>
</body>
</html>
