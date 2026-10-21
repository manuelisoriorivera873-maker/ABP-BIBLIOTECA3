@extends('layouts.app')

@section('title', 'Registrar autor')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Registrar autor</h1>
            <p>Captura los datos completos del autor.</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('autores.store') }}">
            @csrf
            @include('autores.form')
        </form>
    </div>
@endsection
