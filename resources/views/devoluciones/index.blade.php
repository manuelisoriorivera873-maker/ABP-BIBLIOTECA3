@extends('layouts.app')

@section('title', 'Devoluciones')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Devoluciones</h1>
            <p>Ahora cada movimiento de devolución puede incluir uno o varios libros del mismo préstamo.</p>
        </div>
    </div>

    <div class="panel mb-4">
        <form class="filter-bar" method="GET">
            <input class="form-control" name="buscar" value="{{ $buscar }}" placeholder="ID préstamo, usuario, título o código de inventario">
            <button class="btn btn-dark" type="submit">Buscar</button>
            <a class="btn btn-light" href="{{ route('devoluciones.index') }}">Limpiar</a>
        </form>
    </div>

    <div class="panel mb-4">
        <div class="section-title-row mb-3">
            <div>
                <h2 class="h5 mb-1">Préstamos con ejemplares pendientes</h2>
                <p class="text-muted mb-0">Selecciona uno o varios ejemplares pendientes y registra la devolución.</p>
            </div>
            <span class="badge text-bg-warning">{{ $pendientes->total() }} préstamos con pendientes</span>
        </div>

        <div class="d-grid gap-3">
            @forelse ($pendientes as $prestamo)
                @php
                    $atrasado = $prestamo->fecha_limite->lt(today());
                @endphp
                <div class="record-card">
                    <div class="record-card__header">
                        <div>
                            <div class="record-card__eyebrow">Préstamo #{{ $prestamo->id_prestamo }}</div>
                            <h3>{{ $prestamo->usuario?->persona?->nombre_completo }}</h3>
                            <p>
                                Bibliotecario: {{ $prestamo->bibliotecario?->persona?->nombre_completo }} ·
                                Préstamo: {{ $prestamo->fecha_prestamo->format('d/m/Y') }} ·
                                Límite: {{ $prestamo->fecha_limite->format('d/m/Y') }}
                            </p>
                        </div>
                        <div>
                            @if ($atrasado)
                                <span class="badge text-bg-danger">ATRASADO</span>
                            @else
                                <span class="badge text-bg-success">ACTIVO</span>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('devoluciones.registrar', $prestamo) }}" onsubmit="return confirm('¿Registrar la devolución de los ejemplares seleccionados?')">
                        @csrf

                        <div class="return-grid">
                            @foreach ($prestamo->detalles_pendientes as $detalle)
                                <label class="selection-card">
                                    <input type="checkbox" name="detalles[]" value="{{ $detalle->id_detalle }}">
                                    <div>
                                        <strong>{{ $detalle->ejemplar?->libro?->titulo }}</strong>
                                        <span>{{ $detalle->ejemplar?->codigo_inventario }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mt-3">
                            <div class="return-meta">
                                <label class="form-label mb-1">Fecha de devolución</label>
                                <input class="form-control" type="date" name="fecha_devolucion" value="{{ now()->toDateString() }}" min="{{ $prestamo->fecha_prestamo->toDateString() }}" required>
                            </div>

                            <div>
                                @if (auth()->user()->puede('devoluciones', 'crear'))
                                    <button class="btn btn-success" type="submit">Registrar devolución parcial o total</button>
                                @else
                                    <small class="text-muted">Solo consulta</small>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            @empty
                <div class="text-center text-muted py-4">No hay préstamos con ejemplares pendientes de devolución.</div>
            @endforelse
        </div>

        <div class="mt-3">{{ $pendientes->links() }}</div>
    </div>

    <div class="panel">
        <div class="section-title-row mb-3">
            <div>
                <h2 class="h5 mb-1">Historial de devoluciones</h2>
                <p class="text-muted mb-0">Cada folio muestra exactamente los ejemplares devueltos en ese movimiento.</p>
            </div>
            <span class="badge text-bg-secondary">{{ $devoluciones->total() }} movimientos</span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID devolución</th>
                        <th>ID préstamo</th>
                        <th>Usuario</th>
                        <th>Ejemplares devueltos</th>
                        <th>Bibliotecario</th>
                        <th>Fecha de devolución</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devoluciones as $devolucion)
                        <tr>
                            <td>{{ $devolucion->id_devolucion }}</td>
                            <td>{{ $devolucion->id_prestamo }}</td>
                            <td>{{ $devolucion->prestamo?->usuario?->persona?->nombre_completo }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($devolucion->detalles as $detalle)
                                        <span class="badge text-bg-light border">
                                            {{ $detalle->detallePrestamo?->ejemplar?->codigo_inventario }} ·
                                            {{ $detalle->detallePrestamo?->ejemplar?->libro?->titulo }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>{{ $devolucion->prestamo?->bibliotecario?->persona?->nombre_completo }}</td>
                            <td>{{ $devolucion->fecha_devolucion->format('d/m/Y') }}</td>
                            <td class="text-end text-nowrap">
                                @if (auth()->user()->puede('devoluciones', 'editar'))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('devoluciones.edit', $devolucion) }}">Editar fecha</a>
                                @endif
                                @if (auth()->user()->puede('devoluciones', 'eliminar'))
                                    <form class="d-inline" method="POST" action="{{ route('devoluciones.destroy', $devolucion) }}" onsubmit="return confirm('¿Anular esta devolución? Los ejemplares volverán a estado PRESTADO.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Anular</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Aún no hay devoluciones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $devoluciones->links() }}
    </div>
@endsection
