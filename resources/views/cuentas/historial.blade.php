@extends('layouts.app')
@section('title','Historial de seguridad')
@section('content')
<div class="page-heading"><div><h1>Historial de cambios</h1><p>Auditoría de creación de cuentas, cambios de estado y asignaciones de permisos.</p></div><a class="btn btn-light" href="{{ route('cuentas.index') }}">Volver</a></div>
<div class="panel"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Fecha</th><th>Administrador</th><th>Cuenta afectada</th><th>Evento</th><th>Detalle</th></tr></thead><tbody>@forelse($cambios as $cambio)
<tr><td>{{ $cambio->created_at }}</td><td>{{ $cambio->actor ?? 'Sin cuenta' }}</td><td>{{ $cambio->destino }}</td><td>{{ $cambio->evento }}</td><td><details><summary>Ver cambios</summary><pre class="small" style="white-space:pre-wrap;word-break:break-word">{{ json_encode(json_decode($cambio->detalle,true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details></td></tr>
@empty<tr><td colspan="5" class="text-center text-muted">Sin cambios registrados.</td></tr>@endforelse</tbody></table></div>{{ $cambios->links() }}</div>
@endsection
