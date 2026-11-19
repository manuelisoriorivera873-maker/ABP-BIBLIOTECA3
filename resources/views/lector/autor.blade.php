@extends('layouts.lector')
@section('title', $autor->nombre_publico)
@section('content')
<a class="volver" href="{{ route('lector.bibliografia', ['tipo'=>'autores']) }}">← Autores</a>
<article class="seguimiento autor-ficha">
    <img class="autor-avatar" src="{{ asset('assets/img/autor-avatar.svg') }}" alt="Avatar de {{ $autor->nombre_publico }}" width="150" height="150">
    <div><span class="sobrelinea">CONOCE A QUIEN ESCRIBE</span><h1>{{ $autor->nombre_publico }}</h1>
        <dl><div><dt>Nacionalidad</dt><dd>{{ $autor->ficha->nacionalidad ?: 'Pendiente de verificar' }}</dd></div>
        @if($autor->ficha->nacimiento)<div><dt>Nacimiento</dt><dd>{{ $autor->ficha->nacimiento->format('d/m/Y') }}</dd></div>@endif
        @if($autor->ficha->fallecimiento)<div><dt>Fallecimiento</dt><dd>{{ $autor->ficha->fallecimiento->format('d/m/Y') }}</dd></div>@endif</dl>
        <p class="biografia-texto">{{ $autor->ficha->biografia }}</p>
        <a href="{{ $autor->ficha->fuente_url }}" target="_blank" rel="noopener noreferrer">Fuente biográfica ↗</a>
    </div>
</article>
<div class="titulo-seccion"><h2>Obras en SAIDE Biblioteca</h2><span>{{ $libros->total() }} títulos</span></div>
<div class="libros-grid">@forelse($libros as $libro)@include('lector.tarjeta')@empty<p class="vacio">Todavía no hay obras registradas.</p>@endforelse</div>
{{ $libros->links() }}
@endsection
