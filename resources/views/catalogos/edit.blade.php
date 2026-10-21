@extends('layouts.app')

@section('title', 'Editar registro')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Editar registro</h1>
            <p>{{ $c['titulo'] }}</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('catalogos.update', [$catalogo, $registro->{$c['pk']}]) }}">
            @csrf
            @method('PUT')
            @include('catalogos.form')
        </form>
    </div>
@endsection
