@extends('layouts.app')
@section('title','Cuentas y permisos')
@section('content')
<div class="page-heading">
    <div>
        <h1>Cuentas y permisos</h1>
        <p>Panel de control de accesos individuales con una vista limpia, profesional y fácil de presentar.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('cuentas.historial') }}">Historial</a>
        <a class="btn btn-primary" href="{{ route('cuentas.create') }}">+ Crear cuenta</a>
    </div>
</div>

<div class="account-grid mb-4">
    <div class="stats-card"><span>Total de cuentas</span><strong>{{ $cuentas->count() }}</strong></div>
    <div class="stats-card"><span>Superadministradores</span><strong>{{ $cuentas->where('rol', 'SUPERADMIN')->count() }}</strong></div>
    <div class="stats-card"><span>Bibliotecarios</span><strong>{{ $cuentas->where('rol', 'BIBLIOTECARIO')->count() }}</strong></div>
    <div class="stats-card"><span>Usuarios lectores</span><strong>{{ $cuentas->where('rol', 'USUARIO')->count() }}</strong></div>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table align-middle permissions-list-table">
            <thead>
                <tr>
                    <th>Cuenta</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Resumen visual de permisos</th>
                    <th class="text-end">Administrar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cuentas as $cuenta)
                    <tr>
                        <td>
                            <div class="account-pill">
                                <div class="account-pill__avatar">{{ mb_substr($cuenta->nombre_visible, 0, 1) }}</div>
                                <div>
                                    <strong>{{ $cuenta->nombre_visible }}</strong>
                                    <div class="small text-muted">{{ $cuenta->nombre_usuario }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge text-bg-primary">{{ $cuenta->rol }}</span></td>
                        <td><span class="badge {{ $cuenta->activo ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $cuenta->activo ? 'Activa' : 'Desactivada' }}</span></td>
                        <td>
                            @if($cuenta->rol === 'SUPERADMIN')
                                <span class="perm-tag perm-tag--full">Acceso total al sistema</span>
                            @else
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($cuenta->permisos->groupBy('modulo') as $modulo => $acciones)
                                        <span class="perm-tag">{{ $modulos[$modulo] ?? $modulo }} · {{ $acciones->count() }}</span>
                                    @endforeach
                                </div>
                                @if($cuenta->permisos->isEmpty())
                                    <small class="text-muted">Sin permisos asignados</small>
                                @endif
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if($cuenta->rol !== 'SUPERADMIN')
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('cuentas.permisos',$cuenta) }}">Matriz de permisos</a>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('cuentas.edit',$cuenta) }}">Cuenta</a>
                            @else
                                <small class="text-muted">Protegida</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Aún no hay cuentas creadas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
