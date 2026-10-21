@extends('layouts.app')

@section('title', 'Nuevo registro')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Nuevo registro</h1>
            <p>{{ $c['titulo'] }}</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('catalogos.store', $catalogo) }}">
            @csrf
            @include('catalogos.form')
        </form>
    </div>
@endsection
