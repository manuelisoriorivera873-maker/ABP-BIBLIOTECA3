@extends('layouts.app')

@section('title', 'Registrar ejemplar')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Registrar ejemplar</h1>
            <p>Registra una copia física de un libro.</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('ejemplares.store') }}">
            @csrf
            @include('ejemplares.form')
        </form>
    </div>
@endsection
