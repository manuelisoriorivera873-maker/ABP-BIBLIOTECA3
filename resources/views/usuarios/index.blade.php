@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Usuarios</h1>
            <p>Administración de personas registradas como usuarios de la biblioteca.</p>
        </div>

        @if (auth()->user()->puede('usuarios', 'crear'))
        <a class="btn btn-primary" href="{{ route('usuarios.create') }}">
            + Registrar usuario
        </a>
        @endif
    </div>

    <div class="panel">
        <form class="filter-bar" method="GET">
            <input
                class="form-control"
                name="buscar"
                value="{{ $buscar }}"
                placeholder="Buscar por nombre, apellidos o teléfono"
            >
            <button class="btn btn-dark" type="submit">Buscar</button>
            <a class="btn btn-light" href="{{ route('usuarios.index') }}">Limpiar</a>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre completo del usuario</th>
                        <th>Teléfono</th>
                        <th>Estado del usuario</th>
                        <th>Préstamos</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <td>{{ $usuario->id_usuario }}</td>
                            <td>{{ $usuario->persona?->nombre_completo }}</td>
                            <td>{{ $usuario->telefono }}</td>
                            <td>
                                <span
                                    class="badge {{ $usuario->estado?->nombre_estado_usuario === 'ACTIVO' ? 'text-bg-success' : 'text-bg-secondary' }}"
                                >
                                    {{ $usuario->estado?->nombre_estado_usuario }}
                                </span>
                            </td>
                            <td>{{ $usuario->prestamos_count }}</td>
                            <td class="text-end text-nowrap">
                                @if (auth()->user()->puede('usuarios', 'editar'))
<a
                                    class="btn btn-sm btn-outline-primary"
                                    href="{{ route('usuarios.edit', $usuario) }}"
                                >
                                    Editar
                                </a>
@endif

                                @if (auth()->user()->puede('usuarios', 'eliminar'))
<form
                                    class="d-inline"
                                    method="POST"
                                    action="{{ route('usuarios.destroy', $usuario) }}"
                                    onsubmit="return confirm('¿Eliminar este usuario?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">
                                        Eliminar
                                    </button>
                                </form>
@endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $usuarios->links() }}
    </div>
@endsection
