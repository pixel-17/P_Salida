@extends('layouts.app')
@section('title', 'Atenciones')
@section('page-title', 'Registro de Atenciones')
 
@section('content')
<div class="page-header">
    <div>
        <h1>Atenciones médicas</h1>
        <p>Historial general de atenciones del tópico</p>
    </div>
    <a href="{{ route('atenciones.create') }}" class="btn btn-primary">+ Nueva atención</a>
</div>
 
<div class="card card-sm" style="margin-bottom:16px;">
    <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div>
            <label style="font-size:13px;font-weight:500;display:block;margin-bottom:5px;">DNI estudiante</label>
            <input type="text" name="dni" value="{{ request('dni') }}" class="form-control" placeholder="12345678" style="width:140px;">
        </div>
        <div>
            <label style="font-size:13px;font-weight:500;display:block;margin-bottom:5px;">Desde</label>
            <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="form-control" style="width:150px;">
        </div>
        <div>
            <label style="font-size:13px;font-weight:500;display:block;margin-bottom:5px;">Hasta</label>
            <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="form-control" style="width:150px;">
        </div>
        <div>
            <label style="font-size:13px;font-weight:500;display:block;margin-bottom:5px;">Motivo</label>
            <select name="motivo_id" class="form-control" style="width:180px;">
                <option value="">Todos</option>
                @foreach($motivos as $m)
                    <option value="{{ $m->id }}" {{ request('motivo_id') == $m->id ? 'selected' : '' }}>{{ $m->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;align-items:center;gap:8px;padding-bottom:2px;">
            <input type="checkbox" name="recurrente" id="recurrente" value="1" {{ request('recurrente') ? 'checked' : '' }} style="width:16px;height:16px;">
            <label for="recurrente" style="font-size:13px;font-weight:500;cursor:pointer;">Solo recurrentes</label>
        </div>
        <button type="submit" class="btn btn-accent">🔍 Filtrar</button>
        <a href="{{ route('atenciones.index') }}" class="btn btn-outline">Limpiar</a>
    </form>
</div>
 
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Estudiante</th>
                    <th>Motivo</th>
                    <th>Diagnóstico</th>
                    <th>Enfermera</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($atenciones as $at)
                <tr>
                    <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--muted);white-space:nowrap;">{{ $at->fecha->format('d/m/Y H:i') }}</td>
                    <td>
                        <div style="font-weight:500;">{{ $at->estudiante->nombre_completo }}</div>
                        <div style="font-size:12px;color:var(--muted);">{{ $at->estudiante->dni }}</div>
                    </td>
                    <td style="font-size:13px;">{{ $at->motivo->nombre }}</td>
                    <td style="font-size:13px;max-width:180px;">{{ Str::limit($at->diagnostico, 50) ?? '—' }}</td>
                    <td style="font-size:13px;">{{ $at->enfermera->name }}</td>
                    <td>
                        <div style="display:flex;flex-direction:column;gap:4px;">
                            @if($at->es_recurrente)
                                <span class="badge badge-warning">⚠ Recurrente</span>
                            @endif
                            @if($at->derivado)
                                <span class="badge badge-primary">Derivado</span>
                            @endif
                            @if(!$at->es_recurrente && !$at->derivado)
                                <span class="badge badge-success">Normal</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="{{ route('atenciones.show', $at) }}" class="btn btn-outline btn-sm">Ver</a>
                            <a href="{{ route('atenciones.edit', $at) }}" class="btn btn-outline btn-sm">Editar</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:40px;">No se encontraron atenciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">{{ $atenciones->withQueryString()->links() }}</div>
</div>
@endsection