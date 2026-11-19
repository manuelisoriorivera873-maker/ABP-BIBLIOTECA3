@foreach([
    ['lector.inicio','libros','ver','Inicio','⌂'],
    ['lector.explorar','libros','ver','Explorar libros','▤'],
    ['lector.favoritos','libros','ver','Mis favoritos','⭐'],
    ['lector.bibliografia','autores','ver','Autores','✎'],
    ['lector.bibliografia','categorias','ver','Categorías','▦'],
    ['lector.bibliografia','editoriales','ver','Editoriales','▥'],
    ['lector.bandeja','solicitudes','crear','Solicitar préstamo','＋'],
    ['lector.solicitudes','solicitudes','ver','Mis solicitudes','▤'],
    ['lector.prestamos','solicitudes','ver','Mis préstamos','↔'],
] as [$ruta,$modulo,$accion,$nombre,$icono])
    @if($cuentaActual->puede($modulo,$accion))
        @php($activa=request()->routeIs($ruta) && ($ruta!=='lector.bibliografia' || request()->route('tipo')===$modulo))
        <a class="nav-link {{ $activa ? 'active' : '' }}" href="{{ route($ruta,$ruta==='lector.bibliografia' ? $modulo : []) }}" @if($activa) aria-current="page" @endif>
            <span class="nav-icon" aria-hidden="true">{{ $icono }}</span>{{ $nombre }}
            @if($ruta==='lector.bandeja')<span class="contador">{{ array_sum(session('lector.bandeja',[])) }}</span>@endif
        </a>
    @endif
@endforeach
