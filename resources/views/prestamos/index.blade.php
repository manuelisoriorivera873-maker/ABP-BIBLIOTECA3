@extends('layouts.app')

@section('title', 'Préstamos')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Préstamos</h1>
            <p>Control de préstamos activos, atrasados y devueltos.</p>
        </div>

        @if (auth()->user()->puede('prestamos', 'crear'))
        <a class="btn btn-primary" href="{{ route('prestamos.create') }}">
            + Nuevo préstamo
        </a>
        @endif
    </div>

    <div class="panel">
        <form class="filter-bar" method="GET">
            <input
                class="form-control"
                name="buscar"
                value="{{ $buscar }}"
                placeholder="Usuario, título, ISBN o código de inventario"
            >

            <select class="form-select" name="estado">
                <option value="todos" @selected($filtro === 'todos')>
                    Todos los préstamos
                </option>
                <option value="activos" @selected($filtro === 'activos')>
                    Activos
                </option>
                <option value="atrasados" @selected($filtro === 'atrasados')>
                    Atrasados
                </option>
                <option value="devueltos" @selected($filtro === 'devueltos')>
                    Devueltos
                </option>
            </select>

            <button class="btn btn-dark" type="submit">Filtrar</button>
            <a class="btn btn-light" href="{{ route('prestamos.index') }}">Limpiar</a>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario del préstamo</th>
                        <th>Bibliotecario responsable</th>
                        <th>Ejemplares prestados</th>
                        <th>Fecha de préstamo</th>
                        <th>Fecha límite</th>
                        <th>Estado del préstamo</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prestamos as $prestamo)
                        @php
                            $devuelto = $prestamo->esta_devuelto;
                            $atrasado = $prestamo->esta_atrasado;
                        @endphp

                        <tr>
                            <td>{{ $prestamo->id_prestamo }}</td>
                            <td>{{ $prestamo->usuario?->persona?->nombre_completo }}</td>
                            <td>{{ $prestamo->bibliotecario?->persona?->nombre_completo }}</td>
                            <td>
                                @foreach ($prestamo->detalles as $detalle)
                                    <span class="badge text-bg-light border">
                                        {{ $detalle->ejemplar?->codigo_inventario }}
                                        · {{ $detalle->ejemplar?->libro?->titulo }}
                                    </span>
                                @endforeach
                            </td>
                            <td>{{ $prestamo->fecha_prestamo->format('d/m/Y') }}</td>
                            <td>{{ $prestamo->fecha_limite->format('d/m/Y') }}</td>
                            <td>
                                <span
                                    class="badge {{ $devuelto ? 'text-bg-success' : ($atrasado ? 'text-bg-danger' : 'text-bg-primary') }}"
                                >
                                    {{ $devuelto ? 'DEVUELTO' : ($atrasado ? 'ATRASADO' : 'ACTIVO') }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                @if (!$devuelto)
                                    @if (auth()->user()->puede('prestamos', 'editar'))
<a
                                        class="btn btn-sm btn-outline-primary"
                                        href="{{ route('prestamos.edit', $prestamo) }}"
                                    >
                                        Editar
                                    </a>
@endif

                                    @if (auth()->user()->puede('devoluciones', 'crear'))
<a
                                        class="btn btn-sm btn-success"
                                        href="{{ route('devoluciones.index', ['buscar' => $prestamo->id_prestamo]) }}"
                                    >
                                        Registrar devolución
                                    </a>
@endif
                                @else
                                    <small class="text-muted">
                                        Devolución registrada
                                    </small>
                                @endif

                                @if (auth()->user()->puede('prestamos', 'eliminar'))
<form
                                    class="d-inline"
                                    method="POST"
                                    action="{{ route('prestamos.destroy', $prestamo) }}"
                                    onsubmit="return confirm('¿Eliminar este préstamo y su historial relacionado?')"
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
                                No hay préstamos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $prestamos->links() }}
    </div>
@endsection
