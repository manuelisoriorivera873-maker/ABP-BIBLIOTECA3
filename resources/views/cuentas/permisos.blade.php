@extends('layouts.app')
@section('title','Permisos por cuenta')
@section('content')
<div class="page-heading">
    <div>
        <h1>Matriz de permisos</h1>
        <p>Asignación sofisticada, clara y controlada por módulo y operación.</p>
    </div>
    <a class="btn btn-light" href="{{ route('cuentas.index') }}">Volver a cuentas</a>
</div>

<div class="account-details mb-4">
    <div class="account-details__main">
        <div class="account-pill__avatar large">{{ mb_substr($cuenta->nombre_visible,0,1) }}</div>
        <div>
            <strong>{{ $cuenta->nombre_visible }}</strong>
            <div class="text-secondary small">{{ $cuenta->nombre_usuario }} · {{ $cuenta->rol }}</div>
        </div>
    </div>
    <div class="text-secondary small">
        @if($cuenta->rol==='USUARIO')
            El rol USUARIO solamente puede consultar libros, autores, categorías y editoriales.
        @else
            Puedes habilitar o deshabilitar operaciones CRUD por cada módulo de trabajo.
        @endif
    </div>
</div>

<div class="panel">
    <form method="POST" action="{{ route('cuentas.permisos.guardar',$cuenta) }}">
        @csrf
        @method('PUT')

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
                                @php($habilitado = \App\Support\PermisosBiblioteca::permiteRol($cuenta->rol,$modulo,$accion))
                                @php($valor = $modulo.':'.$accion)
                                <td class="text-center {{ !$habilitado ? 'perm-disabled' : '' }}">
                                    <label class="matrix-toggle">
                                        <input type="checkbox" name="permisos[]" value="{{ $valor }}" aria-label="{{ $etiqueta }} - {{ $accion }}" @checked(in_array($valor, old('permisos',$asignados),true)) @disabled(!$habilitado)>
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
            <button type="submit" class="btn btn-primary">Guardar permisos</button>
        </div>
    </form>
</div>
@endsection
