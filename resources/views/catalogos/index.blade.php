@extends('layouts.app')

@section('title', $c['titulo'])

@section('content')
    <div class="page-heading">
        <div>
            <h1>{{ $c['titulo'] }}</h1>
            <p>Administración del catálogo: {{ mb_strtolower($c['titulo']) }}.</p>
        </div>

        @if (auth()->user()->puede(($catalogo), 'crear'))
        <a class="btn btn-primary" href="{{ route('catalogos.create', $catalogo) }}">
            + Nuevo registro
        </a>
        @endif
    </div>

    <div class="panel">
        <form class="filter-bar" method="GET">
            <input
                class="form-control"
                name="buscar"
                value="{{ $buscar }}"
                placeholder="Buscar en {{ mb_strtolower($c['titulo']) }}"
            >
            <button class="btn btn-dark" type="submit">Buscar</button>
            <a class="btn btn-light" href="{{ route('catalogos.index', $catalogo) }}">
                Limpiar
            </a>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ $c['etiqueta'] }}</th>
                        @if ($c['descripcion'])
                            <th>Descripción de la ubicación</th>
                        @endif
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        <tr>
                            <td>{{ $registro->{$c['pk']} }}</td>
                            <td>{{ $registro->{$c['campo']} }}</td>
                            @if ($c['descripcion'])
                                <td>{{ $registro->descripcion ?: 'Sin descripción' }}</td>
                            @endif
                            <td class="text-end text-nowrap">
                                @if (auth()->user()->puede(($catalogo), 'editar'))
<a
                                    class="btn btn-sm btn-outline-primary"
                                    href="{{ route('catalogos.edit', [$catalogo, $registro->{$c['pk']}]) }}"
                                >
                                    Editar
                                </a>
@endif

                                @if (auth()->user()->puede(($catalogo), 'eliminar'))
<form
                                    class="d-inline"
                                    method="POST"
                                    action="{{ route('catalogos.destroy', [$catalogo, $registro->{$c['pk']}]) }}"
                                    onsubmit="return confirm('¿Eliminar este registro?')"
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
                            <td
                                colspan="{{ $c['descripcion'] ? 4 : 3 }}"
                                class="text-center text-muted py-4"
                            >
                                No hay registros en este catálogo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $registros->links() }}
    </div>
@endsection
