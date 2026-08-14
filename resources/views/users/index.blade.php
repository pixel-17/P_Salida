@extends('layouts.app')
@section('title','Usuarios')
@section('page-title','Gestión de Usuarios')

@section('content')
<div class="page-header">
    <div><h1>Usuarios del sistema</h1><p>Administración de cuentas de acceso a S_Topic</p></div>
    <a href="{{ route('users.create') }}" class="btn btn-primary">+ Nuevo usuario</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="num-col">#</th>
                    <th>Nombre completo</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Registrado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $i => $u)
                <tr>
                    <td class="num-col">{{ ($users->currentPage()-1)*$users->perPage()+$i+1 }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--primary));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;">
                                {{ strtoupper(substr($u->name,0,1)) }}
                            </div>
                            <div>
                                <div style="font-weight:500;">{{ $u->nombre_completo }}</div>
                                @if($u->id===auth()->id())
                                    <div style="font-size:11px;color:var(--accent);">← Tú</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="font-size:13px;color:var(--muted);">{{ $u->email }}</td>
                    <td>
                        <span class="badge {{ $u->isAdmin()?'badge-warning':'badge-primary' }}">
                            {{ $u->isAdmin()?'👑 Admin':'🩺 Enfermera' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $u->activo?'badge-success':'badge-danger' }}">
                            {{ $u->activo?'● Activo':'○ Inactivo' }}
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--muted);font-family:'DM Mono',monospace;">{{ $u->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <a href="{{ route('users.edit',$u) }}" class="btn btn-outline btn-sm">Editar</a>
                            @if($u->id!==auth()->id())
                            <form method="POST" action="{{ route('users.toggle',$u) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="btn btn-sm {{ $u->activo?'btn-danger':'btn-accent' }}"
                                    onclick="return confirm('{{ $u->activo ? 'Desactivar '.$u->name.'?' : 'Activar '.$u->name.'?' }}')">
                                    {{ $u->activo?'Desactivar':'Activar' }}
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:40px;">No hay usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">{{ $users->links() }}</div>
</div>
@endsection
