@extends('layouts.app')
@section('title','Cambiar Contraseña')
@section('page-title','Mi Cuenta')

@section('content')
<div class="page-header">
    <div><h1>Cambiar contraseña</h1><p>Actualiza tu contraseña de acceso al sistema</p></div>
</div>

<div style="max-width:480px;">
<div class="card">
    <p class="section-title">Seguridad de la cuenta</p>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding:14px;background:var(--bg);border-radius:var(--radius-sm);">
        <div class="av" style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--primary));display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0;">
            {{ strtoupper(substr(auth()->user()->name,0,1)) }}
        </div>
        <div>
            <div style="font-weight:600;">{{ auth()->user()->nombre_completo }}</div>
            <div style="font-size:13px;color:var(--muted);">{{ auth()->user()->email }}</div>
        </div>
    </div>

    <form method="POST" action="{{ route('perfil.password') }}">
    @csrf @method('PUT')
        <div class="form-group">
            <label>Contraseña actual <span style="color:var(--danger)">*</span></label>
            <input type="password" name="password_actual" class="form-control {{ $errors->has('password_actual')?'is-invalid':'' }}" autocomplete="current-password">
            @error('password_actual')<div class="invalid-feedback">⚠ {{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label>Nueva contraseña <span style="color:var(--danger)">*</span></label>
            <input type="password" name="password" id="new-pass" class="form-control {{ $errors->has('password')?'is-invalid':'' }}" autocomplete="new-password">
            <div id="pass-strength" style="margin-top:6px;height:4px;border-radius:2px;background:var(--border);overflow:hidden;"><div id="pass-bar" style="height:100%;width:0;transition:width .3s,background .3s;border-radius:2px;"></div></div>
            <div id="pass-label" style="font-size:11px;color:var(--muted);margin-top:3px;"></div>
            @error('password')<div class="invalid-feedback">⚠ {{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label>Confirmar nueva contraseña <span style="color:var(--danger)">*</span></label>
            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;padding:11px;">🔒 Actualizar contraseña</button>
    </form>
</div>
</div>

<script>
document.getElementById('new-pass').addEventListener('input', function() {
    const v = this.value;
    let score = 0;
    if (v.length >= 8) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const colors = ['#ef4444','#f97316','#eab308','#22c55e'];
    const labels = ['Muy débil','Débil','Aceptable','Fuerte'];
    const bar = document.getElementById('pass-bar');
    bar.style.width = (score * 25) + '%';
    bar.style.background = colors[score-1] || '#e5e7eb';
    document.getElementById('pass-label').textContent = v.length ? labels[score-1] || '' : '';
});
</script>
@endsection
