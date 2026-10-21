@extends('layouts.app')

@section('title', 'Editar autor')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Editar autor</h1>
            <p>{{ $autor->nombre_completo }}</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('autores.update', $autor) }}">
            @csrf
            @method('PUT')
            @include('autores.form')
        </form>
    </div>
@endsection
