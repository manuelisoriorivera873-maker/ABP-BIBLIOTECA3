@forelse($libro->autores as $autor)
@if(!$loop->first) · @endif
@if($autor->ficha && $puedeVerAutores)<a href="{{ route('lector.autor', $autor->ficha->publico) }}">{{ $autor->nombre_publico }}</a>@else{{ $autor->nombre_publico }}@endif
@empty Autoría pendiente de revisión @endforelse
