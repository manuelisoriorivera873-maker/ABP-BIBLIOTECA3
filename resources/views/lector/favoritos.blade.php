@extends('layouts.lector')
@section('title','Mis favoritos')
@section('content')
<header class="pagina-titulo"><span class="sobrelinea">MI BIBLIOTECA PERSONAL</span><h1>Mis favoritos</h1><p>Guardar un libro no lo reserva ni genera un préstamo.</p></header>
<div class="libros-grid">@forelse($libros as $libro)@include('lector.tarjeta')@empty<div class="vacio"><h2>Aún no tienes favoritos</h2><a href="{{ route('lector.explorar') }}">Explorar libros</a></div>@endforelse</div>
{{ $libros->links() }}
@endsection
