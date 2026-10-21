@extends('layouts.app')

@section('title', 'Registrar préstamo')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Registrar préstamo</h1>
            <p>Un préstamo puede incluir varios ejemplares en una sola operación.</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('prestamos.store') }}">
            @csrf
            @include('prestamos.form')
        </form>
    </div>
@endsection
