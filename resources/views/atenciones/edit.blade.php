@extends('layouts.app')
@section('title','Editar Atención')
@section('page-title','Editar Atención')

@section('content')
<div class="page-header">
    <div>
        <h1>Editar Atención #{{ $atencion->id }}</h1>
        <p>Solo administradores pueden modificar atenciones</p>
    </div>
    <a href="{{ route('atenciones.show',$atencion) }}" class="btn btn-outline">← Volver</a>
</div>

<div style="background:var(--warn-lt);border:1px solid #fdba74;border-radius:9px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#c2410c;display:flex;align-items:center;gap:8px;">
    🔒 Estás editando como <strong>Administrador</strong>. La fecha y la enfermera original no pueden modificarse.
</div>

<form method="POST" action="{{ route('atenciones.update',$atencion) }}">
@csrf @method('PUT')
<input type="hidden" name="estudiante_id" value="{{ $atencion->estudiante_id }}">
<div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start;">
    <div class="card">
        <p class="section-title">Datos de la atención</p>

        {{-- Estudiante — no editable --}}
        <div class="form-group">
            <label>Estudiante</label>
            <input type="text" class="form-control" value="{{ $atencion->estudiante->nombre_completo }} — {{ $atencion->estudiante->dni }}" disabled>
        </div>

        {{-- Fecha — no editable --}}
        <div class="form-group">
            <label>Fecha de atención</label>
            <input type="text" class="form-control" value="{{ $atencion->fecha->format('d/m/Y H:i') }}" disabled style="font-family:'DM Mono',monospace;">
            <input type="hidden" name="fecha" value="{{ $atencion->fecha->format('Y-m-d H:i:s') }}">
            <div style="font-size:11px;color:var(--muted);margin-top:3px;">La fecha no puede modificarse</div>
        </div>

        {{-- Enfermera — no editable --}}
        <div class="form-group">
            <label>Atendido por</label>
            <input type="text" class="form-control" value="{{ $atencion->enfermera->nombre_completo }}" disabled>
            <div style="font-size:11px;color:var(--muted);margin-top:3px;">El personal que registró la atención no puede modificarse</div>
        </div>

        <div class="form-group">
            <label>Motivo de consulta <span style="color:var(--danger)">*</span></label>
            <select name="motivo_id" class="form-control" required>
                @foreach($categorias as $cat)
                    <optgroup label="{{ $cat->nombre }}">
                        @foreach($cat->motivos as $m)
                            <option value="{{ $m->id }}" {{ $atencion->motivo_id==$m->id?'selected':'' }}>{{ $m->nombre }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Diagnóstico</label>
            <textarea name="diagnostico" class="form-control" rows="3">{{ $atencion->diagnostico }}</textarea>
        </div>
        <div class="form-group">
            <label>Tratamiento</label>
            <textarea name="tratamiento" class="form-control" rows="3">{{ $atencion->tratamiento }}</textarea>
        </div>
        <div class="form-group">
            <label>Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="2">{{ $atencion->observaciones }}</textarea>
        </div>
    </div>

    <div>
        <div class="card">
            <p class="section-title">Estado</p>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px 0;border-bottom:1px solid var(--border);">
                <input type="checkbox" name="derivado" value="1" {{ $atencion->derivado?'checked':'' }} style="width:16px;height:16px;accent-color:var(--primary);">
                <span style="font-size:13.5px;">Derivado a especialista</span>
            </label>
        </div>
        <div style="margin-top:12px;display:flex;flex-direction:column;gap:8px;">
            <button type="submit" class="btn btn-primary" style="width:100%;padding:11px;">💾 Guardar cambios</button>
            <a href="{{ route('atenciones.show',$atencion) }}" class="btn btn-outline" style="width:100%;justify-content:center;">Cancelar</a>
        </div>
    </div>
</div>
</form>
@endsection
