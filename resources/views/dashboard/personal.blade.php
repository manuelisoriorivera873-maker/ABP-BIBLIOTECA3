@extends('layouts.app')
@section('title', 'Escritorio de bibliotecario')
@section('content')
<div class="page-heading"><div><h1>Bienvenido, {{ auth()->user()->nombre_visible }}</h1><p>Los módulos siguientes están autorizados para tu cuenta. Para solicitar cambios, acude al SUPERADMIN.</p></div></div>
<div class="row g-3">
@forelse ($modulos as $modulo => $etiqueta)
<div class="col-md-4"><div class="panel h-100"><h3 class="h6">{{ $etiqueta }}</h3><p class="small text-muted">Operaciones disponibles según tus permisos.</p><a class="btn btn-outline-primary btn-sm" href="{{ in_array($modulo,['libros','autores','ejemplares','personas','usuarios','bibliotecarios','prestamos','devoluciones','solicitudes'],true) ? route($modulo.'.index') : route('catalogos.index',$modulo) }}">Abrir módulo</a></div></div>
@empty
<div class="col-12"><div class="alert alert-info">Tu cuenta no tiene módulos de consulta habilitados. Solicita acceso al SUPERADMIN.</div></div>
@endforelse
</div>
@endsection
