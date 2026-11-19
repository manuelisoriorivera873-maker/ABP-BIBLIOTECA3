@foreach($libro->recursosDigitales->filter(fn($recurso) => $recurso->accesible) as $digital)
<a class="boton boton-suave" href="{{ route('lector.leer',$digital->publico) }}" @if($digital->tipo!=='integrado') target="_blank" rel="noopener noreferrer" @endif>{{ $digital->tipo==='previa' ? 'Vista previa oficial (no es el libro completo)' : ($digital->tipo==='externo' ? 'Leer en línea · fuente externa' : 'Leer en línea') }} · {{ strtoupper($digital->formato) }}</a><small class="nota">{{ $digital->edicion }}</small>
@endforeach
