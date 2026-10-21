@extends('layouts.app')

@section('title', 'Ejemplares')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Ejemplares</h1>
            <p>Inventario físico de la biblioteca.</p>
        </div>

        @if (auth()->user()->puede('ejemplares', 'crear'))
        <a class="btn btn-primary" href="{{ route('ejemplares.create') }}">
            + Registrar ejemplar
        </a>
        @endif
    </div>

    <div class="panel">
        <form class="filter-bar" method="GET">
            <input
                class="form-control"
                name="buscar"
                value="{{ $buscar }}"
                placeholder="Código de inventario, título o ISBN"
            >

            <select class="form-select" name="estado">
                <option value="">Todos los estados</option>
                @foreach ($estados as $estadoRegistro)
                    <option
                        value="{{ $estadoRegistro->id_estado_ejemplar }}"
                        @selected((int) $estado === $estadoRegistro->id_estado_ejemplar)
                    >
                        {{ $estadoRegistro->nombre_estado }}
                    </option>
                @endforeach
            </select>

            <button class="btn btn-dark" type="submit">Filtrar</button>
            <a class="btn btn-light" href="{{ route('ejemplares.index') }}">Limpiar</a>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código de inventario</th>
                        <th>Libro</th>
                        <th>Ubicación física</th>
                        <th>Estado del ejemplar</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ejemplares as $ejemplar)
                        <tr>
                            <td>{{ $ejemplar->id_ejemplar }}</td>
                            <td><strong>{{ $ejemplar->codigo_inventario }}</strong></td>
                            <td>
                                {{ $ejemplar->libro?->titulo }}
                                <small class="d-block text-muted">
                                    ISBN {{ $ejemplar->libro?->isbn }}
                                </small>
                            </td>
                            <td>{{ $ejemplar->ubicacion?->nombre_ubicacion }}</td>
                            <td>
                                @php
                                    $nombreEstado = $ejemplar->estado?->nombre_estado ?? '';
                                    $claseEstado = match ($nombreEstado) {
                                        'DISPONIBLE' => 'text-bg-success',
                                        'PRESTADO' => 'text-bg-primary',
                                        'DANADO' => 'text-bg-warning',
                                        'BAJA' => 'text-bg-secondary',
                                        default => 'text-bg-light',
                                    };
                                @endphp

                                <span class="badge {{ $claseEstado }}">
                                    {{ $nombreEstado }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                @if (auth()->user()->puede('ejemplares', 'editar'))
<a
                                    class="btn btn-sm btn-outline-primary"
                                    href="{{ route('ejemplares.edit', $ejemplar) }}"
                                >
                                    Editar
                                </a>
@endif

                                @if (auth()->user()->puede('ejemplares', 'eliminar'))
<form
                                    class="d-inline"
                                    method="POST"
                                    action="{{ route('ejemplares.destroy', $ejemplar) }}"
                                    onsubmit="return confirm('¿Eliminar este ejemplar?')"
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
                                No hay ejemplares registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $ejemplares->links() }}
    </div>
@endsection
