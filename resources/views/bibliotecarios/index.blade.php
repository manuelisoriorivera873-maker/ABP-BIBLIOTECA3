@extends('layouts.app')

@section('title', 'Bibliotecarios')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Bibliotecarios</h1>
            <p>Personal responsable de registrar los préstamos.</p>
        </div>

        @if (auth()->user()->puede('bibliotecarios', 'crear'))
        <a class="btn btn-primary" href="{{ route('bibliotecarios.create') }}">
            + Registrar bibliotecario
        </a>
        @endif
    </div>

    <div class="panel">
        <form class="filter-bar" method="GET">
            <input
                class="form-control"
                name="buscar"
                value="{{ $buscar }}"
                placeholder="Buscar bibliotecario por nombre"
            >
            <button class="btn btn-dark" type="submit">Buscar</button>
            <a class="btn btn-light" href="{{ route('bibliotecarios.index') }}">Limpiar</a>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre completo del bibliotecario</th>
                        <th>Préstamos registrados</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bibliotecarios as $bibliotecario)
                        <tr>
                            <td>{{ $bibliotecario->id_bibliotecario }}</td>
                            <td>{{ $bibliotecario->persona?->nombre_completo }}</td>
                            <td>{{ $bibliotecario->prestamos_count }}</td>
                            <td class="text-end text-nowrap">
                                @if (auth()->user()->puede('bibliotecarios', 'editar'))
<a
                                    class="btn btn-sm btn-outline-primary"
                                    href="{{ route('bibliotecarios.edit', $bibliotecario) }}"
                                >
                                    Editar
                                </a>
@endif

                                @if (auth()->user()->puede('bibliotecarios', 'eliminar'))
<form
                                    class="d-inline"
                                    method="POST"
                                    action="{{ route('bibliotecarios.destroy', $bibliotecario) }}"
                                    onsubmit="return confirm('¿Eliminar este bibliotecario?')"
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
                            <td colspan="4" class="text-center text-muted py-4">
                                No hay bibliotecarios registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $bibliotecarios->links() }}
    </div>
@endsection
