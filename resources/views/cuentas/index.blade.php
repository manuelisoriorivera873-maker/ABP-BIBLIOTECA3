@extends('layouts.app')
@section('title','Cuentas y permisos')
@section('content')
<div class="page-heading">
    <div>
        <h1>Cuentas y permisos</h1>
        <p>Cuentas con permisos heredados de su rol. La asignación de roles se realiza desde Personas.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('cuentas.historial') }}">Historial</a>
        <a class="btn btn-primary" href="{{ route('personas.index') }}">Asignar roles desde Personas</a>
    </div>
</div>

<div class="account-grid mb-4">
    <div class="stats-card"><span>Total de cuentas</span><strong>{{ $totales->sum() }}</strong></div>
    <div class="stats-card"><span>Superadministradores</span><strong>{{ $totales['SUPERADMIN'] ?? 0 }}</strong></div>
    <div class="stats-card"><span>Bibliotecarios</span><strong>{{ $totales['BIBLIOTECARIO'] ?? 0 }}</strong></div>
    <div class="stats-card"><span>Usuarios lectores</span><strong>{{ $totales['USUARIO'] ?? 0 }}</strong></div>
</div>

<div class="panel mb-4">
    <h2 class="h5">Administración de permisos por rol</h2>
    <a class="btn btn-outline-primary" href="{{ route('cuentas.roles.permisos', 'BIBLIOTECARIO') }}">BIBLIOTECARIO</a>
    <a class="btn btn-outline-primary" href="{{ route('cuentas.roles.permisos', 'USUARIO') }}">USUARIO</a>
</div>
<div class="panel">
    <form class="filter-bar" method="GET">
        <input class="form-control" name="buscar" value="{{ $buscar }}" placeholder="Buscar cuenta o persona">
        <button class="btn btn-dark" type="submit">Buscar</button>
        <a class="btn btn-light" href="{{ route('cuentas.index') }}">Limpiar</a>
    </form>
    <div class="table-responsive">
        <table class="table align-middle permissions-list-table">
            <thead>
                <tr>
                    <th>ID</th><th>Cuenta y persona</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Permisos heredados</th>
                    <th class="text-end">Administrar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cuentas as $cuenta)
                    <tr>
                        <td>{{ $cuenta->id_cuenta }}</td>
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
                                <span class="text-muted">{{ count($permisosRol[$cuenta->rol] ?? []) }} autorizaciones del rol {{ $cuenta->rol }}</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if($cuenta->rol !== 'SUPERADMIN')
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('cuentas.roles.permisos', $cuenta->rol) }}">Matriz del rol</a>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('cuentas.edit',$cuenta) }}">Cuenta</a>
                            @else
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('cuentas.edit',$cuenta) }}">Cuenta</a>
                            @endif
                            @php($idPersona = $cuenta->empleado?->id_persona ?? $cuenta->lector?->id_persona)
                            @if($idPersona)
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('personas.edit', ['persona' => $idPersona, 'cuenta' => $cuenta->id_cuenta]) }}">Rol en Personas</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aún no hay cuentas creadas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $cuentas->links() }}
</div>
@endsection
