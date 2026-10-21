@extends('layouts.app')

@section('title', 'Editar bibliotecario')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Editar bibliotecario</h1>
            <p>{{ $bibliotecario->persona?->nombre_completo }}</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('bibliotecarios.update', $bibliotecario) }}">
            @csrf
            @method('PUT')
            @include('bibliotecarios.form')
        </form>
    </div>
@endsection
