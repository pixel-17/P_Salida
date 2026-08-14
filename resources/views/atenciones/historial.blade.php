@extends('layouts.app')
@section('title', 'Historial Clínico')
@section('page-title', 'Historial Clínico')
 
@section('content')
<div class="page-header">
    <div>
        <h1>Historial de: {{ $estudiante->nombre_completo }}</h1>
        <p>DNI: {{ $estudiante->dni }} · Total: {{ $atenciones->count() }} atenciones</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('atenciones.reporte', $estudiante) }}" class="btn btn-outline" target="_blank">🖨 Imprimir</a>
        <a href="{{ route('atenciones.create') }}?estudiante_id={{ $estudiante->id }}" class="btn btn-accent">+ Nueva atención</a>
        <a href="{{ route('estudiantes.show', $estudiante) }}" class="btn btn-outline">← Ficha</a>
    </div>
</div>
 
@if($atenciones->where('es_recurrente', true)->count() > 0)
<div class="recurrente-alert" style="margin-bottom:20px;">
    ⚠️ Este estudiante tiene {{ $atenciones->where('es_recurrente', true)->count() }} caso(s) recurrente(s) registrado(s).
</div>
@endif
 
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Fecha</th><th>Motivo</th><th>Diagnóstico</th><th>Tratamiento</th><th>Enfermera</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($atenciones as $i => $at)
                <tr>
                    <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--muted);">{{ $i + 1 }}</td>
                    <td style="font-family:'DM Mono',monospace;font-size:12px;white-space:nowrap;">{{ $at->fecha->format('d/m/Y H:i') }}</td>
                    <td style="font-size:13px;font-weight:500;">{{ $at->motivo->nombre }}</td>
                    <td style="font-size:13px;max-width:180px;">{{ Str::limit($at->diagnostico, 60) ?? '—' }}</td>
                    <td style="font-size:13px;max-width:160px;">{{ Str::limit($at->tratamiento, 50) ?? '—' }}</td>
                    <td style="font-size:13px;">{{ $at->enfermera->name }}</td>
                    <td>
                        @if($at->es_recurrente)<span class="badge badge-warning">⚠ Recurrente</span>
                        @else<span class="badge badge-success">Normal</span>@endif
                    </td>
                    <td><a href="{{ route('atenciones.show', $at) }}" class="btn btn-outline btn-sm">Ver</a></td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:40px;">Sin atenciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection