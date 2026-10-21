@extends('layouts.app')

@section('title', 'Registrar libro')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Registrar libro</h1>
            <p>Registra el libro y sus relaciones sin capturas duplicadas.</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('libros.store') }}">
            @csrf
            @include('libros.form')
        </form>
    </div>
@endsection
