@extends('layouts.app')
@section('title','Nueva Atención')
@section('page-title','Nueva Atención Médica')

@section('content')
<div class="page-header">
    <div><h1>Nueva Atención Médica</h1><p>Busca al estudiante por DNI para iniciar el registro</p></div>
    <a href="{{ route('atenciones.index') }}" class="btn btn-outline">← Volver</a>
</div>

<form method="POST" action="{{ route('atenciones.store') }}" id="form-atencion">
@csrf

{{-- PASO 1: Búsqueda --}}
<div class="card" style="margin-bottom:16px;">
    <p class="section-title">Paso 1 — Buscar estudiante por DNI</p>
    <div style="display:flex;gap:10px;max-width:480px;">
        <input type="text" id="buscar-dni" class="form-control" placeholder="Ingresa 8 dígitos del DNI..." maxlength="8" inputmode="numeric" style="font-family:'DM Mono',monospace;font-size:16px;letter-spacing:2px;">
        <button type="button" id="btn-buscar" onclick="buscarEstudiante()" class="btn btn-accent" style="white-space:nowrap;">
            🔍 Buscar
        </button>
    </div>
    <div id="spinner" style="display:none;margin-top:12px;color:var(--muted);font-size:13px;">⏳ Buscando...</div>

    {{-- Resultado encontrado --}}
    <div id="resultado-encontrado" style="display:none;margin-top:14px;">
        <div style="background:var(--success-lt);border:1.5px solid #86efac;border-radius:var(--radius);padding:16px;display:flex;justify-content:space-between;align-items:flex-start;gap:16px;">
            <div>
                <div style="font-weight:700;font-size:16px;color:#15803d;" id="est-nombre"></div>
                <div style="font-size:13px;color:var(--muted);margin-top:2px;" id="est-info"></div>
                <div id="est-sangre" style="margin-top:8px;"></div>
            </div>
            <button type="button" onclick="limpiarBusqueda()" class="btn btn-outline btn-sm" style="flex-shrink:0;">✕ Cambiar</button>
        </div>
        <div id="est-alergias" style="margin-top:8px;display:none;"></div>
        <div id="est-imc" style="margin-top:8px;display:none;"></div>
    </div>

    {{-- No encontrado --}}
    <div id="no-encontrado" style="display:none;margin-top:14px;">
        <div style="background:var(--danger-lt);border:1.5px solid #fca5a5;border-radius:var(--radius);padding:16px;">
            <div style="font-weight:600;color:var(--danger);margin-bottom:8px;">❌ Estudiante no encontrado</div>
            <div style="font-size:13px;color:var(--text2);margin-bottom:12px;">No existe un estudiante con DNI <strong id="dni-no-encontrado"></strong> en el sistema.</div>
            <div style="display:flex;gap:10px;">
                <a id="btn-registrar-nuevo" href="{{ route('estudiantes.create') }}" class="btn btn-primary btn-sm">+ Registrar estudiante</a>
                <button type="button" onclick="limpiarBusqueda()" class="btn btn-outline btn-sm">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- PASO 2: Formulario de atención (bloqueado hasta buscar) --}}
<div id="form-atencion-body" style="opacity:.4;pointer-events:none;transition:opacity .3s;">
    <div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start;">
        <div class="card">
            <p class="section-title">Paso 2 — Datos de la atención</p>
            <input type="hidden" name="estudiante_id" id="estudiante-id">
            <div class="form-group">
                <label>Motivo de consulta <span style="color:var(--danger)">*</span></label>
                <select name="motivo_id" class="form-control {{ $errors->has('motivo_id')?'is-invalid':'' }}" required>
                    <option value="">Seleccionar motivo...</option>
                    @foreach($categorias as $cat)
                        <optgroup label="{{ $cat->nombre }}">
                            @foreach($cat->motivos as $m)
                                <option value="{{ $m->id }}" {{ old('motivo_id')==$m->id?'selected':'' }}>{{ $m->nombre }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('motivo_id')<div class="invalid-feedback">⚠ {{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Fecha y hora de atención</label>
                <input type="text" class="form-control" value="{{ now()->format('d/m/Y H:i') }}" disabled style="font-family:'DM Mono',monospace;">
                <input type="hidden" name="fecha" value="{{ now()->format('Y-m-d H:i:s') }}">
                <div style="font-size:11px;color:var(--muted);margin-top:4px;">La fecha se registra automáticamente al guardar</div>
            </div>
            <div class="form-group">
                <label>Diagnóstico</label>
                <textarea name="diagnostico" class="form-control" rows="3" placeholder="Describe el diagnóstico...">{{ old('diagnostico') }}</textarea>
            </div>
            <div class="form-group">
                <label>Tratamiento</label>
                <textarea name="tratamiento" class="form-control" rows="3" placeholder="Tratamiento administrado al paciente...">{{ old('tratamiento') }}</textarea>
            </div>
            <div class="form-group">
                <label>Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2" placeholder="Observaciones adicionales...">{{ old('observaciones') }}</textarea>
            </div>
        </div>

        <div>
            <div class="card">
                <p class="section-title">Opciones</p>
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px 0;border-bottom:1px solid var(--border);">
                    <input type="checkbox" name="derivado" value="1" {{ old('derivado')?'checked':'' }} style="width:16px;height:16px;accent-color:var(--primary);flex-shrink:0;">
                    <span style="font-size:13.5px;">Derivado a especialista</span>
                </label>
                <div style="padding-top:14px;">
                    <div style="background:var(--primary-lt);border-radius:var(--radius-sm);padding:12px;font-size:12px;color:var(--primary);line-height:1.5;">
                        ℹ️ Si el estudiante tiene 3 o más atenciones por el mismo motivo, el sistema marcará automáticamente el caso como <strong>recurrente</strong>.
                    </div>
                </div>
            </div>
            <div style="margin-top:12px;">
                <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;font-size:14px;">💾 Registrar atención</button>
            </div>
        </div>
    </div>
</div>
</form>

<script>
let estudianteEncontrado = false;

async function buscarEstudiante() {
    const dni = document.getElementById('buscar-dni').value.trim();
    if (dni.length !== 8 || !/^\d{8}$/.test(dni)) {
        showToast('warning','DNI inválido','Ingresa exactamente 8 dígitos numéricos.');
        return;
    }

    document.getElementById('resultado-encontrado').style.display = 'none';
    document.getElementById('no-encontrado').style.display = 'none';
    document.getElementById('spinner').style.display = 'block';
    document.getElementById('btn-buscar').disabled = true;

    try {
        const res  = await fetch(`/estudiantes/buscar/dni?dni=${dni}`);
        const data = await res.json();
        document.getElementById('spinner').style.display = 'none';
        document.getElementById('btn-buscar').disabled = false;

        if (!res.ok) {
            document.getElementById('dni-no-encontrado').textContent = dni;
            document.getElementById('no-encontrado').style.display = 'block';
            document.getElementById('form-atencion-body').style.opacity = '.4';
            document.getElementById('form-atencion-body').style.pointerEvents = 'none';
            return;
        }

        // Llenar datos
        document.getElementById('estudiante-id').value = data.id;
        document.getElementById('est-nombre').textContent = data.nombres + ' ' + data.apellidos;
        document.getElementById('est-info').textContent =
            'DNI: ' + data.dni +
            (data.carrera ? ' · ' + data.carrera.nombre : '') +
            (data.semestre ? ' · ' + data.semestre : '');

        if (data.ficha_medica?.tipo_sangre) {
            document.getElementById('est-sangre').innerHTML =
                `<span class="badge badge-danger" style="font-size:13px;">🩸 ${data.ficha_medica.tipo_sangre}</span>`;
        }

        if (data.ficha_medica?.alergias) {
            const a = document.getElementById('est-alergias');
            a.style.display = 'block';
            a.innerHTML = `<div class="recurrente-alert">⚠️ Alergias: ${data.ficha_medica.alergias}</div>`;
        }

        if (data.ficha_medica?.peso && data.ficha_medica?.talla) {
            const imc = (data.ficha_medica.peso / (data.ficha_medica.talla ** 2)).toFixed(1);
            const d = document.getElementById('est-imc');
            d.style.display = 'block';
            d.innerHTML = `<div style="font-size:12px;color:var(--muted);">IMC: <strong>${imc}</strong> · Peso: ${data.ficha_medica.peso}kg · Talla: ${data.ficha_medica.talla}m</div>`;
        }

        document.getElementById('resultado-encontrado').style.display = 'block';
        document.getElementById('form-atencion-body').style.opacity = '1';
        document.getElementById('form-atencion-body').style.pointerEvents = 'all';
        estudianteEncontrado = true;
        showToast('success','Estudiante encontrado', data.nombres + ' ' + data.apellidos);

    } catch(e) {
        document.getElementById('spinner').style.display = 'none';
        document.getElementById('btn-buscar').disabled = false;
        showToast('danger','Error de conexión','No se pudo completar la búsqueda.');
    }
}

function limpiarBusqueda() {
    document.getElementById('buscar-dni').value = '';
    document.getElementById('resultado-encontrado').style.display = 'none';
    document.getElementById('no-encontrado').style.display = 'none';
    document.getElementById('est-alergias').style.display = 'none';
    document.getElementById('est-imc').style.display = 'none';
    document.getElementById('est-sangre').innerHTML = '';
    document.getElementById('estudiante-id').value = '';
    document.getElementById('form-atencion-body').style.opacity = '.4';
    document.getElementById('form-atencion-body').style.pointerEvents = 'none';
    estudianteEncontrado = false;
    document.getElementById('buscar-dni').focus();
}

// Enter en DNI
document.getElementById('buscar-dni').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); buscarEstudiante(); }
});

// Validar antes de enviar
document.getElementById('form-atencion').addEventListener('submit', function(e) {
    if (!document.getElementById('estudiante-id').value) {
        e.preventDefault();
        showToast('warning','Sin estudiante','Busca y selecciona un estudiante antes de registrar.');
    }
});
</script>
@endsection
