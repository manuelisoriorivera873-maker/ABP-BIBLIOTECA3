@extends('layouts.app')

@section('title', 'Personas')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Personas</h1>
            <p>Registro general de personas relacionadas con la biblioteca.</p>
        </div>

        @if (auth()->user()->puede('personas', 'crear'))
        <a class="btn btn-primary" href="{{ route('personas.create') }}">
            + Registrar persona
        </a>
        @endif
    </div>

    <div class="panel">
        <form class="filter-bar" method="GET">
            <input
                class="form-control"
                name="buscar"
                value="{{ $buscar }}"
                placeholder="Buscar por nombre o apellidos"
            >
            <button class="btn btn-dark" type="submit">Buscar</button>
            <a class="btn btn-light" href="{{ route('personas.index') }}">Limpiar</a>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre completo</th>
                        <th>Relación con el sistema</th>
                        @if(auth()->user()->rol === 'SUPERADMIN')
                            <th>Rol de acceso</th>
                        @endif
                        <th>Información relacionada</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($personas as $persona)
                        <tr>
                            <td>{{ $persona->id_persona }}</td>
                            <td>{{ $persona->nombre_completo }}</td>
                            <td>
                                @if ($persona->usuario)
                                    <span class="badge text-bg-primary">LECTOR</span>
                                @endif

                                @if ($persona->bibliotecario)
                                    <span class="badge text-bg-warning">PERSONAL BIBLIOTECARIO</span>
                                @endif

                                @if (!$persona->usuario && !$persona->bibliotecario)
                                    <span class="badge text-bg-secondary">SIN ASIGNAR</span>
                                @endif
                            </td>
                            @if(auth()->user()->rol === 'SUPERADMIN')
                                <td>
                                    @php($cuentasVinculadas = collect([$persona->usuario?->cuentaAcceso, $persona->bibliotecario?->cuentaAcceso])->filter()->unique('id_cuenta'))
                                    @forelse($cuentasVinculadas as $acceso)
                                        <div><span class="badge text-bg-primary">{{ $acceso->rol }}</span></div>
                                        <small class="text-muted">{{ $acceso->nombre_usuario }} · {{ $acceso->activo ? 'Activa' : 'Inactiva' }}</small>
                                    @empty
                                        <span class="badge text-bg-secondary">SIN CUENTA</span>
                                    @endforelse
                                </td>
                            @endif
                            <td>
                                @if ($persona->usuario)
                                    <div>Teléfono: {{ $persona->usuario->telefono }}</div>
                                    <small class="text-muted">
                                        Estado: {{ $persona->usuario->estado?->nombre_estado_usuario ?? 'Sin estado' }}
                                    </small>
                                @elseif ($persona->bibliotecario)
                                    <span>Bibliotecario #{{ $persona->bibliotecario->id_bibliotecario }}</span>
                                @else
                                    <span class="text-muted">Sin datos adicionales</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a
                                    class="btn btn-sm btn-outline-dark"
                                    href="{{ route('personas.show', $persona) }}"
                                >
                                    Ver
                                </a>

                                @if (auth()->user()->puede('personas', 'editar'))
<a
                                    class="btn btn-sm btn-outline-primary"
                                    href="{{ route('personas.edit', $persona) }}"
                                >
                                    Editar
                                </a>
@endif

                                @if (auth()->user()->puede('personas', 'eliminar'))
<form
                                    class="d-inline"
                                    method="POST"
                                    action="{{ route('personas.destroy', $persona) }}"
                                    onsubmit="return confirm('¿Eliminar esta persona?')"
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
                            <td colspan="{{ auth()->user()->rol === 'SUPERADMIN' ? 6 : 5 }}" class="text-center text-muted py-4">
                                No hay personas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $personas->links() }}
    </div>
@endsection
