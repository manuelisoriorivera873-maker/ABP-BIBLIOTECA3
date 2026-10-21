@extends('layouts.app')

@section('title', 'Editar persona')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Editar persona</h1>
            <p>{{ $persona->nombre_completo }}</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('personas.update', $persona) }}">
            @csrf
            @method('PUT')
            @include('personas.form')
        </form>
    </div>
@endsection
