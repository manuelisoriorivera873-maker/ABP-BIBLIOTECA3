<article class="bibliografia-tarjeta">
    <img class="autor-avatar" src="{{ asset('assets/img/autor-avatar.svg') }}" alt="Avatar de {{ $registro->nombre_publico }}" loading="lazy" width="80" height="80">
    <h2>{{ $registro->nombre_publico }}</h2><small>{{ $registro->ficha?->nacionalidad }}</small>
    <p>{{ $registro->ficha ? \Illuminate\Support\Str::limit($registro->ficha->biografia, 170) : 'Información biográfica pendiente de verificación.' }}</p>
    @if($registro->ficha)<a href="{{ route('lector.autor', $registro->ficha->publico) }}">Biografía y obras · {{ $registro->libros_count }} títulos →</a>@else<a href="{{ route('lector.explorar', ['buscar'=>$registro->persona->apellido_paterno]) }}">Explorar obras →</a>@endif
</article>
