@extends('layouts.app')
@section('title', 'Nuevo Usuario')
@section('page-title', 'Nuevo Usuario')
 
@section('content')
<div class="page-header">
    <div><h1>Crear usuario</h1><p>Agrega una nueva cuenta de acceso al sistema</p></div>
    <a href="{{ route('users.index') }}" class="btn btn-outline">← Volver</a>
</div>
 
<div style="max-width:600px;">
<form method="POST" action="{{ route('users.store') }}">
@csrf
<div class="card">
    <p class="section-title">Datos del usuario</p>
    <div class="form-grid">
        <div class="form-group">
            <label>Nombres <span style="color:var(--danger)">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label>Apellidos <span style="color:var(--danger)">*</span></label>
            <input type="text" name="apellidos" value="{{ old('apellidos') }}" class="form-control {{ $errors->has('apellidos') ? 'is-invalid' : '' }}">
            @error('apellidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group col-span-2">
            <label>Correo electrónico <span style="color:var(--danger)">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group col-span-2">
            <label>Rol <span style="color:var(--danger)">*</span></label>
            <select name="rol" class="form-control {{ $errors->has('rol') ? 'is-invalid' : '' }}">
                <option value="enfermero" {{ old('rol') === 'enfermero' ? 'selected' : '' }}>Enfermera / Enfermero</option>
                <option value="admin" {{ old('rol') === 'admin' ? 'selected' : '' }}>Administrador</option>
            </select>
        </div>
        <div class="form-group">
            <label>Contraseña <span style="color:var(--danger)">*</span></label>
            <input type="password" name="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label>Confirmar contraseña <span style="color:var(--danger)">*</span></label>
            <input type="password" name="password_confirmation" class="form-control">
        </div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;">
        <a href="{{ route('users.index') }}" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary">💾 Crear usuario</button>
    </div>
</div>
</form>
</div>
@endsection