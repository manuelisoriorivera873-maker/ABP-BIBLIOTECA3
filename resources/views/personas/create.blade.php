@extends('layouts.app')

@section('title', 'Registrar persona')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Registrar persona</h1>
            <p>Alta de una persona en el registro general de la biblioteca.</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('personas.store') }}">
            @csrf
            @include('personas.form')
        </form>
    </div>
@endsection
