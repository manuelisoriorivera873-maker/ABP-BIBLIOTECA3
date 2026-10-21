@extends('layouts.app')

@section('title', 'Detalle de persona')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Detalle de persona</h1>
            <p>{{ $persona->nombre_completo }}</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-light" href="{{ route('personas.index') }}">Volver</a>
            @if (auth()->user()->puede('personas', 'editar'))
                <a class="btn btn-primary" href="{{ route('personas.edit', $persona) }}">Editar</a>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="panel h-100">
                <div class="panel-header">
                    <div>
                        <h2>Datos generales</h2>
                        <p>Información almacenada en la tabla personas.</p>
                    </div>
                </div>

                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <strong>ID de persona</strong>
                            <div>{{ $persona->id_persona }}</div>
                        </div>
                        <div class="col-md-8">
                            <strong>Nombre completo</strong>
                            <div>{{ $persona->nombre_completo }}</div>
                        </div>
                        <div class="col-md-4">
                            <strong>Nombre(s)</strong>
                            <div>{{ $persona->nombre }}</div>
                        </div>
                        <div class="col-md-4">
                            <strong>Apellido paterno</strong>
                            <div>{{ $persona->apellido_paterno }}</div>
                        </div>
                        <div class="col-md-4">
                            <strong>Apellido materno</strong>
                            <div>{{ $persona->apellido_materno }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel h-100">
                <div class="panel-header">
                    <div>
                        <h2>Relación con el sistema</h2>
                        <p>Vínculos de esta persona con otros módulos.</p>
                    </div>
                </div>

                <div class="p-3">
                    @if (auth()->user()->puede('usuarios', 'ver') && $persona->usuario)
                        <div class="mb-3">
                            <span class="badge text-bg-primary mb-2">USUARIO</span>
                            <div><strong>ID:</strong> {{ $persona->usuario->id_usuario }}</div>
                            <div><strong>Teléfono:</strong> {{ $persona->usuario->telefono }}</div>
                            <div>
                                <strong>Estado:</strong>
                                {{ $persona->usuario->estado?->nombre_estado_usuario ?? 'Sin estado' }}
                            </div>
                            @if (auth()->user()->puede('prestamos', 'ver'))
                                <div><strong>Préstamos:</strong> {{ $persona->usuario->prestamos->count() }}</div>
                            @endif
                        </div>
                    @endif

                    @if (auth()->user()->puede('bibliotecarios', 'ver') && $persona->bibliotecario)
                        <div class="mb-3">
                            <span class="badge text-bg-warning mb-2">BIBLIOTECARIO</span>
                            <div><strong>ID:</strong> {{ $persona->bibliotecario->id_bibliotecario }}</div>
                            @if (auth()->user()->puede('prestamos', 'ver'))
                                <div>
                                    <strong>Préstamos atendidos:</strong>
                                    {{ $persona->bibliotecario->prestamos->count() }}
                                </div>
                            @endif
                        </div>
                    @endif

                    @if (
                        (!auth()->user()->puede('usuarios', 'ver') || !$persona->usuario)
                        && (!auth()->user()->puede('bibliotecarios', 'ver') || !$persona->bibliotecario)
                    )
                        <div class="text-muted">
                            No hay relaciones visibles para los permisos de esta cuenta.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
