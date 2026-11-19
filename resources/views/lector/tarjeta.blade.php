<article class="libro-tarjeta">
    <a class="libro-portada" href="{{ route('lector.ficha',$libro->ficha->publico) }}" aria-label="Ver detalles de {{ $libro->titulo_publico }}">
        <img src="{{ $libro->ficha->portada_url ?: asset('assets/img/portada-generica.svg') }}" alt="{{ $libro->ficha->portada_url ? 'Portada de '.$libro->titulo_publico : 'Portada no disponible de '.$libro->titulo_publico }}" loading="lazy" decoding="async" width="240" height="340" data-portada-alternativa="{{ asset('assets/img/portada-generica.svg') }}" referrerpolicy="no-referrer">
        <span class="disponibilidad {{ $libro->disponibles ? 'hay' : '' }}">{{ $libro->disponibles === 1 ? 'Último ejemplar' : ($libro->disponibles > 1 ? 'Disponible' : 'No disponible') }}</span>
    </a>
    <div class="libro-datos"><span class="etiqueta">{{ $libro->categorias->first()?->nombre_publico ?: 'Por clasificar' }}</span>
        @include('lector.estrella')
        <h3><a href="{{ route('lector.ficha',$libro->ficha->publico) }}">{{ $libro->titulo_publico }}</a></h3>
        <p class="autor">@include('lector.autores-enlaces')</p>
        <p class="editorial">{{ $libro->ficha->editorial_verificada ?: 'Edición por verificar' }}</p>
        @if($libro->ficha->sinopsis)<p class="editorial libro-sinopsis">{{ $libro->ficha->sinopsis }}</p>@endif
        @if($libro->ficha->idioma)<p class="editorial">{{ ['spa'=>'Español','eng'=>'Inglés'][$libro->ficha->idioma] ?? $libro->ficha->idioma }}</p>@endif
        @include('lector.digital')
        <div class="tarjeta-acciones"><a class="boton boton-suave" href="{{ route('lector.ficha',$libro->ficha->publico) }}">Ver detalles</a>
        @if($puedeSolicitar)
        <form method="POST" action="{{ route('lector.seleccionar',$libro->ficha->publico) }}">@csrf<input type="hidden" name="cantidad" value="1"><button class="boton boton-primario" type="submit" @disabled(!$libro->disponibles)>Agregar a mi solicitud</button></form>
        @endif</div>
    </div>
</article>
