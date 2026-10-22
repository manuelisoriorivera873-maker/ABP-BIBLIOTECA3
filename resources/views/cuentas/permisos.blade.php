@extends('layouts.app')
@section('title','Permisos por rol')
@section('content')
<p class="text-muted">Al asignar Crear, Editar o Eliminar se incluye Consultar para acceder a los botones del módulo.</p>
<div class="page-heading">
    <div>
        <h1>Administración de permisos por rol</h1>
        <p>Una única matriz compartida por todas las cuentas del rol, actuales y futuras.</p>
    </div>
    <a class="btn btn-light" href="{{ route('cuentas.index') }}">Volver a cuentas</a>
</div>

<div class="d-flex flex-wrap gap-2 mb-4" aria-label="Seleccionar rol">
    @foreach(['BIBLIOTECARIO', 'USUARIO'] as $opcion)
        <a class="btn {{ $rol === $opcion ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('cuentas.roles.permisos', $opcion) }}">{{ $opcion }}</a>
    @endforeach
</div>
<p><strong>Rol: {{ $rol }}</strong>. Los cambios se aplican a todas sus cuentas en la siguiente operación.</p>
@if($rol === 'USUARIO')
    <p class="text-muted">USUARIO hereda los permisos autorizados por SUPERADMIN. Sus préstamos se limitan a registros propios; los movimientos definitivos corresponden al personal.</p>
@endif
<div class="panel">
    <form method="POST" action="{{ route('cuentas.roles.guardar', $rol) }}" data-matriz-permisos>
        @csrf
        @method('PUT')
        <input type="hidden" name="matriz_rol" value="{{ $rol }}">
        @php($seleccionados = old('matriz_rol') === $rol ? old('permisos', []) : $asignados)

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="table-responsive">
            <table class="table permissions-matrix align-middle">
                <thead>
                    <tr>
                        <th>Módulo</th>
                        @foreach($acciones as $accion)
                            <th class="text-center text-uppercase">{{ $accion }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($modulos as $modulo => $etiqueta)
                        <tr>
                            <td>
                                <strong>{{ $etiqueta }}</strong>
                                <div class="small text-muted">{{ $modulo }}</div>
                            </td>
                            @foreach($acciones as $accion)
                                @php($habilitado = \App\Support\PermisosBiblioteca::permiteRol($rol,$modulo,$accion))
                                @php($valor = $modulo.':'.$accion)
                                <td class="text-center {{ !$habilitado ? 'perm-disabled' : '' }}">
                                    <label class="matrix-toggle" for="permiso-{{ $rol }}-{{ $modulo }}-{{ $accion }}">
                                        <input id="permiso-{{ $rol }}-{{ $modulo }}-{{ $accion }}" type="checkbox" name="permisos[]" value="{{ $valor }}" data-modulo="{{ $modulo }}" data-accion="{{ $accion }}" aria-label="{{ $etiqueta }} - {{ $accion }}" @checked(in_array($valor, $seleccionados, true)) @disabled(!$habilitado)>
                                        <span></span>
                                    </label>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex gap-2 justify-content-end mt-3">
            <a href="{{ route('cuentas.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar permisos del rol</button>
        </div>
    </form>
</div>
@endsection
