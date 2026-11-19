@extends('layouts.lector')
@section('title','Leer · '.$recurso->libro->titulo_publico)
@section('content')
<a class="volver" href="{{ route('lector.ficha',$recurso->libro->ficha->publico) }}">← Volver a la ficha</a>
<header class="pagina-titulo"><h1>{{ $recurso->libro->titulo_publico }}</h1><p>{{ $recurso->edicion }} · {{ ['spa'=>'Español','eng'=>'Inglés'][$recurso->idioma] ?? $recurso->idioma }}</p></header>
<p class="nota">Esta versión digital es independiente de la edición impresa y no utiliza ejemplares físicos. {{ $recurso->licencia }}</p>
<p><a href="{{ $recurso->fuente }}" target="_blank" rel="noopener noreferrer">Fuente digital</a> · <a href="{{ $recurso->licencia_url }}" target="_blank" rel="noopener noreferrer">Condiciones de uso</a></p>
<div class="d-flex flex-wrap align-items-center gap-2"><button class="boton boton-suave" type="button" id="lectura-anterior" disabled>← Anterior</button><button class="boton boton-suave" type="button" id="lectura-siguiente" disabled>Siguiente →</button><label for="lectura-tamano">{{ $recurso->formato==='pdf' ? 'Ampliación' : 'Tamaño del texto' }}</label><select class="form-select w-auto" id="lectura-tamano">@foreach([75,100,125,150,175,200] as $n)<option value="{{ $n }}" @selected(($progreso->tamano ?? 100)===$n)>{{ $n }}%</option>@endforeach</select></div>
<p role="status" id="lectura-estado">Cargando lectura…</p>
<div id="lectura-visor" data-formato="{{ $recurso->formato }}" data-archivo="{{ route('lector.archivo',$recurso->publico) }}" data-progreso="{{ route('lector.progreso',$recurso->publico) }}" data-posicion="{{ $progreso->posicion ?? '' }}" data-csrf="{{ csrf_token() }}" data-vendor="{{ asset('assets/vendor/lectura') }}" aria-label="Contenido del libro"></div>
<noscript>Activa JavaScript para utilizar el lector integrado.</noscript>
@endsection
@push('scripts')
<script src="{{ asset('assets/vendor/lectura/jszip.min.js') }}" defer></script>
<script src="{{ asset('assets/vendor/lectura/epub.min.js') }}" defer></script>
<script src="{{ asset('assets/js/lectura.js') }}" defer></script>
@endpush
