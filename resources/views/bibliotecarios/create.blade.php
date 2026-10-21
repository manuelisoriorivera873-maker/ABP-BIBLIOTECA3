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
            @include('bibliotecarios.form')
        </form>
    </div>
@endsection
