@extends('layouts.app')
@section('title', 'Editar Usuario')
@section('page-title', 'Editar Usuario')
 
@section('content')
<div class="page-header">
    <div><h1>Editar: {{ $user->nombre_completo }}</h1></div>
    <a href="{{ route('users.index') }}" class="btn btn-outline">← Volver</a>
</div>
 
<div style="max-width:600px;">
<form method="POST" action="{{ route('users.update', $user) }}">
@csrf @method('PUT')
<div class="card">
    <p class="section-title">Datos del usuario</p>
    <div class="form-grid">
        <div class="form-group">
            <label>Nombres</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control">
        </div>
        <div class="form-group">
            <label>Apellidos</label>
            <input type="text" name="apellidos" value="{{ old('apellidos', $user->apellidos) }}" class="form-control">
        </div>
        <div class="form-group col-span-2">
            <label>Correo electrónico</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control">
        </div>
        <div class="form-group col-span-2">
            <label>Rol</label>
            <select name="rol" class="form-control">
                <option value="enfermero" {{ old('rol', $user->rol) === 'enfermero' ? 'selected' : '' }}>Enfermera / Enfermero</option>
                <option value="admin" {{ old('rol', $user->rol) === 'admin' ? 'selected' : '' }}>Administrador</option>
            </select>
        </div>
        <div class="form-group col-span-2" style="display:flex;align-items:center;gap:10px;">
            <input type="checkbox" name="activo" id="activo" value="1" {{ old('activo', $user->activo) ? 'checked' : '' }} style="width:16px;height:16px;accent-color:var(--primary);">
            <label for="activo" style="font-size:14px;cursor:pointer;">Usuario activo</label>
        </div>
        <div class="form-group">
            <label>Nueva contraseña <span style="font-size:12px;color:var(--muted);">(dejar en blanco para no cambiar)</span></label>
            <input type="password" name="password" class="form-control">
        </div>
        <div class="form-group">
            <label>Confirmar contraseña</label>
            <input type="password" name="password_confirmation" class="form-control">
        </div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;">
        <a href="{{ route('users.index') }}" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary">💾 Guardar cambios</button>
    </div>
</div>
</form>
</div>
@endsection