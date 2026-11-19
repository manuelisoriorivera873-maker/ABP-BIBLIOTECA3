@extends('layouts.lector')
@section('title',ucfirst($tipo))
@section('content')
<header class="pagina-titulo"><span class="sobrelinea">EXPLORA OTRAS FORMAS DE ENCONTRAR LIBROS</span><h1>{{ ['autores'=>'Autores','categorias'=>'Categorías','editoriales'=>'Editoriales'][$tipo] }}</h1></header>
<form class="busqueda-principal" method="GET"><label class="visually-hidden" for="buscar-bibliografia">Buscar</label><input id="buscar-bibliografia" name="buscar" value="{{ $buscar }}" maxlength="200" placeholder="Escribe un nombre"><button class="boton boton-primario" type="submit">Buscar</button></form>
<div class="categorias-grid">
@forelse($registros as $registro)
@php($nombre=$tipo==='autores' ? $registro->nombre_publico : $registro->nombre)
@if($tipo==='autores')
@include('lector.autor-tarjeta')
@else
<article class="bibliografia-tarjeta">
<h2>{{ $nombre }}</h2>
@if($registro->ficha?->pais)<small>{{ $registro->ficha->pais }}</small>@endif
<p>{{ $registro->ficha?->descripcion ?: 'Información descriptiva pendiente de verificación.' }}</p>
@if($registro->ficha?->fuente_url)<a href="{{ $registro->ficha->fuente_url }}" target="_blank" rel="noopener noreferrer">Fuente editorial ↗</a>@endif
<a href="{{ route('lector.explorar', [$tipo==='categorias' ? 'categoria' : 'editorial'=>$nombre]) }}">Explorar {{ $registro->libros_count }} títulos →</a>
</article>
@endif
@empty<p class="vacio">No encontramos coincidencias.</p>@endforelse
</div>{{ $registros->links() }}
@endsection
