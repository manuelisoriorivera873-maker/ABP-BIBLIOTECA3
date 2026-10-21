@extends('layouts.app')

@section('title', 'Autores')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Autores</h1>
            <p>Catálogo normalizado de autores enlazados directamente con la tabla de personas.</p>
        </div>

        @if (auth()->user()->puede('autores', 'crear'))
            <a class="btn btn-primary" href="{{ route('autores.create') }}">+ Registrar autor</a>
        @endif
    </div>

    <div class="panel">
        <form class="filter-bar" method="GET">
            <input class="form-control" name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre o apellidos">
            <button class="btn btn-dark" type="submit">Buscar</button>
            <a class="btn btn-light" href="{{ route('autores.index') }}">Limpiar</a>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID autor</th>
                        <th>ID persona</th>
                        <th>Nombre completo</th>
                        <th>Libros relacionados</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($autores as $autor)
                        <tr>
                            <td>{{ $autor->id_autor }}</td>
                            <td>{{ $autor->id_persona }}</td>
                            <td>
                                <strong>{{ $autor->nombre_completo }}</strong>
                                <div class="small text-muted">Registro vinculado a personas</div>
                            </td>
                            <td>{{ $autor->libros_count }}</td>
                            <td class="text-end text-nowrap">
                                @if (auth()->user()->puede('autores', 'editar'))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('autores.edit', $autor) }}">Editar</a>
                                @endif
                                @if (auth()->user()->puede('autores', 'eliminar'))
                                    <form class="d-inline" method="POST" action="{{ route('autores.destroy', $autor) }}" onsubmit="return confirm('¿Eliminar este autor?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay autores registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $autores->links() }}
    </div>
@endsection
