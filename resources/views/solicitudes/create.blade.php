@extends('layouts.app')
@section('title', 'Solicitar préstamo')
@section('content')
<div class="page-heading"><div><h1>Solicitar préstamo</h1><p>Máximo {{ config('prestamos.maximo_ejemplares') }} ejemplares y {{ config('prestamos.maximo_por_titulo') }} por título, contando préstamos activos y solicitudes abiertas. No se aceptan préstamos vencidos.</p></div></div>
<div class="panel">
    <form class="filter-bar" method="GET">
        <input class="form-control" name="buscar" value="{{ $buscar }}" placeholder="Buscar por título o ISBN">
        <button class="btn btn-dark" type="submit">Buscar</button>
        <a class="btn btn-light" href="{{ route('solicitudes.create') }}">Limpiar</a>
    </form>
    <form method="POST" action="{{ route('solicitudes.store') }}" id="solicitarPrestamo">
        @csrf
        <div class="table-responsive"><table class="table align-middle">
            <thead><tr><th>Título</th><th>ISBN</th><th>Disponibles</th><th>Cantidad solicitada</th></tr></thead>
            <tbody>
            @forelse ($libros as $libro)
                <tr><td>{{ $libro->titulo }}</td><td>{{ $libro->isbn }}</td><td>{{ $libro->disponibles }}</td><td>
                    <input class="form-control" type="number" name="libros[{{ $libro->id_libro }}]" min="0" max="{{ min(config('prestamos.maximo_por_titulo'), $libro->disponibles) }}" value="{{ old('libros.'.$libro->id_libro, 0) }}" data-titulo="{{ $libro->titulo }}" aria-label="Cantidad de {{ $libro->titulo }}" @disabled($libro->disponibles === 0)>
                </td></tr>
            @empty
                <tr><td colspan="4">No hay títulos que coincidan con la búsqueda.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="form-section"><h3>Resumen de la solicitud</h3><ul id="resumenSolicitud" aria-live="polite"></ul><p id="totalSolicitud"></p></div>
        <p>La disponibilidad se comprueba nuevamente al aprobar y entregar. La aprobación no reserva ejemplares ni registra un préstamo.</p>
        <button class="btn btn-primary" type="submit">Enviar solicitud</button>
        <a class="btn btn-light" href="{{ route('solicitudes.index') }}">Mis solicitudes</a>
    </form>
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/solicitudes.js') }}"></script>
@endpush
