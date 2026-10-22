@extends('layouts.app')

@section('title', 'Registrar bibliotecario')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Registrar bibliotecario</h1>
            <p>La persona se registra automáticamente junto con el bibliotecario.</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('bibliotecarios.store') }}">
            @csrf
            <label class="form-label" for="persona">Persona existente</label>
            <select class="form-select" name="id_persona" id="persona" required>
                <option value="">Selecciona una persona</option>
                @foreach($personas as $persona)
                    <option value="{{ $persona->id_persona }}" @selected(old('id_persona') == $persona->id_persona)>{{ $persona->nombre_completo }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary mt-3" type="submit">Guardar bibliotecario</button>
        </form>
    </div>
@endsection
