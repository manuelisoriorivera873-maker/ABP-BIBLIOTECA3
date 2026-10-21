@extends('layouts.app')

@section('title', 'Editar devolución')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Editar devolución #{{ $devolucion->id_devolucion }}</h1>
            <p>Ajusta la fecha del movimiento y revisa los ejemplares incluidos.</p>
        </div>
        <a class="btn btn-light" href="{{ route('devoluciones.index') }}">Volver</a>
    </div>

    <div class="panel">
        <div class="row g-4">
            <div class="col-lg-5">
                <h3 class="h5">Datos del movimiento</h3>
                <ul class="list-unstyled text-muted mb-4">
                    <li><strong>Préstamo:</strong> #{{ $devolucion->id_prestamo }}</li>
                    <li><strong>Usuario:</strong> {{ $devolucion->prestamo?->usuario?->persona?->nombre_completo }}</li>
                    <li><strong>Ejemplares:</strong> {{ $devolucion->detalles->count() }}</li>
                </ul>

                <div class="d-flex flex-wrap gap-2">
                    @foreach ($devolucion->detalles as $detalle)
                        <span class="badge text-bg-light border">
                            {{ $detalle->detallePrestamo?->ejemplar?->codigo_inventario }} ·
                            {{ $detalle->detallePrestamo?->ejemplar?->libro?->titulo }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-7">
                <form method="POST" action="{{ route('devoluciones.update', $devolucion) }}">
                    @csrf
                    @method('PUT')

                    <div class="form-section">
                        <h3>Fecha de devolución</h3>
                        <label class="form-label">Fecha *</label>
                        <input class="form-control" type="date" name="fecha_devolucion" value="{{ old('fecha_devolucion', $devolucion->fecha_devolucion->toDateString()) }}" min="{{ $devolucion->prestamo?->fecha_prestamo?->toDateString() }}" required>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a class="btn btn-light" href="{{ route('devoluciones.index') }}">Cancelar</a>
                        <button class="btn btn-primary" type="submit">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
