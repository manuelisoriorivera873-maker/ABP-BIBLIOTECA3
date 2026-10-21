@extends('layouts.app')

@section('title', 'Editar préstamo')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Editar préstamo #{{ $prestamo->id_prestamo }}</h1>
            <p>Modifica datos generales o los ejemplares mientras el préstamo siga activo.</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('prestamos.update', $prestamo) }}">
            @csrf
            @method('PUT')
            @include('prestamos.form')
        </form>
    </div>
@endsection
