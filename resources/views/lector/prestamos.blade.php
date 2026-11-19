@extends('layouts.lector')
@section('title','Mis préstamos')
@section('content')
<header class="pagina-titulo"><span class="sobrelinea">TUS LIBROS, A TU RITMO</span><h1>Mis préstamos</h1><p>Consulta tus lecturas vigentes y el historial de devoluciones.</p></header>
@forelse($prestamos as $prestamo)@php($pendientes=$prestamo->detalles->filter(fn($d)=>$d->devolucionesDetalle->isEmpty())->count())<article class="seguimiento"><div class="seguimiento-cabecera"><span>Préstamo del {{ $prestamo->fecha_prestamo->format('d/m/Y') }}</span><strong class="estado">{{ !$pendientes ? 'DEVUELTO' : ($prestamo->fecha_limite->lt(today()) ? 'ATRASADO' : 'ACTIVO') }}</strong></div><ul>@foreach($prestamo->detalles as $detalle)<li>{{ $detalle->ejemplar->libro->titulo_publico }} · {{ $detalle->devolucionesDetalle->isEmpty() ? 'Por devolver' : 'Devuelto' }}</li>@endforeach</ul><p><strong>Fecha límite: {{ $prestamo->fecha_limite->format('d/m/Y') }}</strong></p></article>@empty<div class="vacio"><h2>Aún no tienes préstamos registrados</h2><p>Cuando tu biblioteca apruebe una solicitud, podrás consultarla aquí.</p></div>@endforelse{{ $prestamos->links() }}
@endsection
