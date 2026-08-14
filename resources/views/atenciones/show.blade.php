@extends('layouts.app')
@section('title', 'Detalle de Atención')
@section('page-title', 'Detalle de Atención')
 
@section('content')
<div class="page-header">
    <div>
        <h1>Atención #{{ $atencion->id }}</h1>
        <p>{{ $atencion->fecha->format('d/m/Y H:i') }}</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('atenciones.edit', $atencion) }}" class="btn btn-outline">Editar</a>
        <a href="{{ route('estudiantes.show', $atencion->estudiante) }}" class="btn btn-outline">Ver ficha</a>
        <a href="{{ route('atenciones.index') }}" class="btn btn-outline">← Volver</a>
    </div>
</div>
 
@if($atencion->es_recurrente)
<div class="recurrente-alert" style="margin-bottom:20px;">
    ⚠️ CASO RECURRENTE — Este estudiante ha sido atendido múltiples veces por el mismo motivo. Se recomienda derivación especializada.
</div>
@endif
 
<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;">
    <div>
        <div class="card">
            <p class="section-title">Información de la atención</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px 24px;font-size:14px;margin-bottom:20px;">
                <div><span style="color:var(--muted);display:block;margin-bottom:3px;">Motivo</span><strong>{{ $atencion->motivo->nombre }}</strong></div>
                <div><span style="color:var(--muted);display:block;margin-bottom:3px;">Categoría</span>{{ $atencion->motivo->categoria->nombre ?? '—' }}</div>
                <div><span style="color:var(--muted);display:block;margin-bottom:3px;">Atendido por</span>{{ $atencion->enfermera->nombre_completo }}</div>
                <div>
                    <span style="color:var(--muted);display:block;margin-bottom:3px;">Estado</span>
                    @if($atencion->derivado)<span class="badge badge-primary">Derivado</span>@endif
                    @if($atencion->es_recurrente)<span class="badge badge-warning">⚠ Recurrente</span>@endif
                    @if(!$atencion->derivado && !$atencion->es_recurrente)<span class="badge badge-success">Normal</span>@endif
                </div>
            </div>
 
            @if($atencion->diagnostico)
            <div style="margin-bottom:16px;">
                <div style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:6px;">Diagnóstico</div>
                <div style="background:var(--bg);border-radius:8px;padding:12px;font-size:14px;line-height:1.6;">{{ $atencion->diagnostico }}</div>
            </div>
            @endif
 
            @if($atencion->tratamiento)
            <div style="margin-bottom:16px;">
                <div style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:6px;">Tratamiento</div>
                <div style="background:var(--bg);border-radius:8px;padding:12px;font-size:14px;line-height:1.6;">{{ $atencion->tratamiento }}</div>
            </div>
            @endif
 
            @if($atencion->observaciones)
            <div>
                <div style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:6px;">Observaciones</div>
                <div style="background:var(--bg);border-radius:8px;padding:12px;font-size:14px;line-height:1.6;">{{ $atencion->observaciones }}</div>
            </div>
            @endif
        </div>
    </div>
 
    <div>
        <div class="card">
            <p class="section-title">👤 Estudiante</p>
            <div style="font-weight:600;font-size:15px;margin-bottom:4px;">{{ $atencion->estudiante->nombre_completo }}</div>
            <div style="font-size:13px;color:var(--muted);margin-bottom:14px;">DNI: {{ $atencion->estudiante->dni }}</div>
 
            @if($atencion->estudiante->fichaMedica)
            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                @if($atencion->estudiante->fichaMedica->tipo_sangre)
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:var(--muted);">Tipo sangre:</span>
                    <span class="badge badge-danger">{{ $atencion->estudiante->fichaMedica->tipo_sangre }}</span>
                </div>
                @endif
                @if($atencion->estudiante->fichaMedica->alergias)
                <div>
                    <div style="color:var(--muted);margin-bottom:4px;">⚠️ Alergias:</div>
                    <div style="background:#fff3e0;border-radius:6px;padding:8px;color:#e65100;">{{ $atencion->estudiante->fichaMedica->alergias }}</div>
                </div>
                @endif
            </div>
            @endif
 
            <div style="margin-top:16px;">
                <a href="{{ route('estudiantes.show', $atencion->estudiante) }}" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;">Ver ficha completa</a>
            </div>
        </div>
    </div>
</div>
@endsection