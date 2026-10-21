@extends('layouts.app')

@section('title', 'Editar ejemplar')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Editar ejemplar</h1>
            <p>{{ $ejemplar->codigo_inventario }}</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('ejemplares.update', $ejemplar) }}">
            @csrf
            @method('PUT')
            @include('ejemplares.form')
        </form>
    </div>
@endsection
