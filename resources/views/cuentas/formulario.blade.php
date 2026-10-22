@extends('layouts.app')
@section('title', 'Editar cuenta')
@section('content')
<div class="page-heading"><div><h1>Editar cuenta</h1><p>Administra las credenciales y el estado. La asignación de roles se realiza desde Editar persona.</p></div><a class="btn btn-light" href="{{ route('cuentas.index') }}">Regresar</a></div>
<div class="panel"><form method="POST" action="{{ route('cuentas.update', $cuenta) }}">@csrf @method('PUT')
<div class="row g-3"><div class="col-md-6"><label class="form-label">Nombre de inicio de sesión</label><input class="form-control" name="nombre_usuario" value="{{ old('nombre_usuario',$cuenta?->nombre_usuario) }}" minlength="4" maxlength="60" required>@error('nombre_usuario')<div class="text-danger small">{{ $message }}</div>@enderror</div>
<div class="col-md-6">
    <label class="form-label">Rol actual</label>
    <p class="form-control-plaintext">{{ $cuenta->rol }}</p>
    @php($idPersona = $cuenta->empleado?->id_persona ?? $cuenta->lector?->id_persona)
    @if($idPersona)
        <a href="{{ route('personas.edit', ['persona' => $idPersona, 'cuenta' => $cuenta->id_cuenta]) }}">Asignar rol en Editar persona</a>
    @else
        <small class="text-muted">Esta cuenta no tiene persona vinculada; requiere revisión administrativa antes de asignar otro rol.</small>
    @endif
</div>
<div class="col-md-6">
    <label class="form-label" for="estadoCuenta">Estado de la cuenta</label>
    <select class="form-select" name="activo" id="estadoCuenta" required>
        <option value="1" @selected(old('activo', (int) $cuenta->activo) == 1)>Activa</option>
        <option value="0" @selected(old('activo', (int) $cuenta->activo) == 0)>Desactivada</option>
    </select>
</div>
<div class="col-md-6"><label class="form-label">Nueva contraseña (opcional)</label><input type="password" class="form-control" name="password" minlength="12" autocomplete="new-password"><small class="text-muted">Mínimo 12 caracteres. Nunca se muestra al SUPERADMIN después del guardado.</small>@error('password')<div class="text-danger small">{{ $message }}</div>@enderror</div>
<div class="col-md-6"><label class="form-label">Confirmar contraseña</label><input type="password" class="form-control" name="password_confirmation" minlength="12" autocomplete="new-password"></div></div>
<div class="d-flex gap-2 mt-4"><button type="submit" class="btn btn-primary">Guardar cuenta</button><a class="btn btn-light" href="{{ route('cuentas.index') }}">Cancelar</a></div></form></div>
@endsection
