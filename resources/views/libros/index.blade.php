@extends('layouts.app')

@section('title', 'Libros')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Libros</h1>
            <p>Catálogo bibliográfico con autores, categorías y editoriales.</p>
        </div>

        @if (auth()->user()->rol === 'USUARIO' && auth()->user()->puede('solicitudes', 'crear'))
        <a class="btn btn-primary" href="{{ route('solicitudes.create') }}">Solicitar préstamo</a>
        @endif
        @if (auth()->user()->puede('libros', 'crear'))
        <a class="btn btn-primary" href="{{ route('libros.create') }}">
            + Registrar libro
        </a>
        @endif
    </div>

    <div class="panel">
        <form class="filter-bar" method="GET">
            <input
                class="form-control"
                name="buscar"
                value="{{ $buscar }}"
                placeholder="Buscar por título, ISBN o autor"
            >
            <button class="btn btn-dark" type="submit">Buscar</button>
            <a class="btn btn-light" href="{{ route('libros.index') }}">Limpiar</a>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ISBN</th>
                        <th>Título del libro</th>
                        <th>Autor(es)</th>
                        <th>Categoría(s)</th>
                        <th>Editorial(es)</th>
                        <th>Ejemplares</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($libros as $libro)
                        <tr>
                            <td>{{ $libro->id_libro }}</td>
                            <td>{{ $libro->isbn }}</td>
                            <td><strong>{{ $libro->titulo }}</strong></td>
                            <td>
                                @foreach ($libro->autores as $autor)
                                    <span class="badge text-bg-light border">
                                        {{ $autor->nombre_completo }}
                                    </span>
                                @endforeach
                            </td>
                            <td>
                                @foreach ($libro->categorias as $categoria)
                                    <span class="badge text-bg-light border">
                                        {{ $categoria->nombre }}
                                    </span>
                                @endforeach
                            </td>
                            <td>
                                @foreach ($libro->editoriales as $editorial)
                                    <span class="badge text-bg-light border">
                                        {{ $editorial->nombre }}
                                    </span>
                                @endforeach
                            </td>
                            <td>{{ $libro->ejemplares_count }}</td>
                            <td class="text-end text-nowrap">
                                @if (auth()->user()->puede('libros', 'editar'))
<a
                                    class="btn btn-sm btn-outline-primary"
                                    href="{{ route('libros.edit', $libro) }}"
                                >
                                    Editar
                                </a>
@endif

                                @if (auth()->user()->puede('libros', 'eliminar'))
<form
                                    class="d-inline"
                                    method="POST"
                                    action="{{ route('libros.destroy', $libro) }}"
                                    onsubmit="return confirm('¿Eliminar este libro?')"
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
                            <td colspan="8" class="text-center text-muted py-4">
                                No hay libros registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $libros->links() }}
    </div>
@endsection
