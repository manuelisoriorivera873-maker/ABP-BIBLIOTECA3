@extends('layouts.app')

@section('title', 'Editar libro')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Editar libro</h1>
            <p>{{ $libro->titulo }}</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('libros.update', $libro) }}">
            @csrf
            @method('PUT')
            @include('libros.form')
        </form>
    </div>
@endsection
