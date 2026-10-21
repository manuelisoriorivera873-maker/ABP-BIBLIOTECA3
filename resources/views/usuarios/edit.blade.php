@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Editar usuario</h1>
            <p>{{ $usuario->persona?->nombre_completo }}</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
            @csrf
            @method('PUT')
            @include('usuarios.form')
        </form>
    </div>
@endsection
