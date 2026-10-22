@extends('layouts.app')
@section('title', auth()->user()->rol === 'USUARIO' ? 'Mis solicitudes' : 'Solicitudes de préstamo')
@section('content')
<div class="page-heading"><div><h1>{{ auth()->user()->rol === 'USUARIO' ? 'Mis solicitudes' : 'Solicitudes de préstamo' }}</h1><p>Consulta el estado y los títulos de cada solicitud.</p></div>
@if (auth()->user()->rol === 'USUARIO' && auth()->user()->puede('solicitudes', 'crear'))
<a class="btn btn-primary" href="{{ route('solicitudes.create') }}">Solicitar préstamo</a>
@endif
</div>
@if (auth()->user()->rol === 'BIBLIOTECARIO' && auth()->user()->id_bibliotecario && auth()->user()->puede('solicitudes', 'editar'))
<div class="panel">
    <form method="POST" action="{{ route('solicitudes.disponibilidad') }}">
        @csrf
        <input type="hidden" name="disponible" value="{{ $disponible ? '0' : '1' }}">
        <p>Recepción automática: {{ $disponible ? 'disponible' : 'no disponible' }}. Se verifica tu sesión y la actividad en esta cola durante los últimos {{ config('prestamos.sesion_operativa_minutos') }} minutos. Recarga para renovar la actividad y recibir solicitudes pendientes.</p>
        <button class="btn btn-outline-primary" type="submit">{{ $disponible ? 'Dejar de recibir solicitudes' : 'Disponible para atender solicitudes' }}</button>
    </form>
</div>
@endif
<div class="panel"><div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>Solicitud</th><th>Lector</th><th>Títulos y cantidades</th><th>Estado</th><th>Responsable</th><th>Atención</th></tr></thead>
    <tbody>
    @forelse ($solicitudes as $solicitud)
        <tr>
            <td>#{{ $solicitud->id_solicitud }}<br>{{ $solicitud->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $solicitud->usuario?->persona?->nombre_completo }}</td>
            <td>@foreach ($solicitud->detalles as $detalle)<div>{{ $detalle->libro?->titulo }} × {{ $detalle->cantidad }}</div>@endforeach</td>
            <td>{{ $solicitud->estado }}@if ($solicitud->motivo_rechazo)<div>{{ $solicitud->motivo_rechazo }}</div>@endif @if ($solicitud->id_prestamo)<div>Préstamo #{{ $solicitud->id_prestamo }}</div>@endif</td>
            <td>{{ $solicitud->responsable?->nombre_visible ?? 'Pendiente de asignación' }}</td>
            <td>
            @if (in_array(auth()->user()->rol, ['BIBLIOTECARIO', 'SUPERADMIN'], true) && auth()->user()->puede('solicitudes', 'editar') && in_array($solicitud->estado, ['PENDIENTE','APROBADA'], true))
                @if ($solicitud->estado === 'PENDIENTE')
                <form method="POST" action="{{ route('solicitudes.update', $solicitud) }}">@csrf @method('PUT')<input type="hidden" name="decision" value="aprobar"><button class="btn btn-outline-primary btn-sm" type="submit">Aprobar</button></form>
                @else
                <form method="POST" action="{{ route('solicitudes.update', $solicitud) }}">@csrf @method('PUT')<input type="hidden" name="decision" value="entregar"><label class="form-label" for="limite-{{ $solicitud->id_solicitud }}">Fecha límite</label><input class="form-control" id="limite-{{ $solicitud->id_solicitud }}" type="date" name="fecha_limite" min="{{ today()->toDateString() }}" required><button class="btn btn-primary btn-sm" type="submit">Confirmar entrega</button></form>
                @endif
                <form method="POST" action="{{ route('solicitudes.update', $solicitud) }}">@csrf @method('PUT')<input type="hidden" name="decision" value="rechazar"><label class="form-label" for="motivo-{{ $solicitud->id_solicitud }}">Motivo del rechazo</label><input class="form-control" id="motivo-{{ $solicitud->id_solicitud }}" name="motivo" maxlength="500" required><button class="btn btn-outline-danger btn-sm" type="submit">Rechazar</button></form>
            @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6">No hay solicitudes registradas.</td></tr>
    @endforelse
    </tbody>
</table></div>{{ $solicitudes->links() }}</div>
@endsection
