@extends('layouts.app')

@section('title', 'Registrar usuario')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Registrar usuario</h1>
            <p>Los datos de persona y usuario se guardan en una sola operación.</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('usuarios.store') }}">
            @csrf
            @include('usuarios.form')
        </form>
    </div>
@endsection
