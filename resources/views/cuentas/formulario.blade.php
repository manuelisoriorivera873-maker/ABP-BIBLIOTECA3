@extends('layouts.app')
@section('title',$cuenta ? 'Editar cuenta' : 'Crear cuenta')
@section('content')
<div class="page-heading"><div><h1>{{ $cuenta ? 'Editar cuenta' : 'Nueva cuenta de acceso' }}</h1><p>Una cuenta vinculada a cada lector o bibliotecario registrado.</p></div><a class="btn btn-light" href="{{ route('cuentas.index') }}">Regresar</a></div>
<div class="panel"><form method="POST" action="{{ $cuenta ? route('cuentas.update',$cuenta) : route('cuentas.store') }}">@csrf @if($cuenta)@method('PUT')@endif
<div class="row g-3"><div class="col-md-6"><label class="form-label">Nombre de inicio de sesión</label><input class="form-control" name="nombre_usuario" value="{{ old('nombre_usuario',$cuenta?->nombre_usuario) }}" minlength="4" maxlength="60" required>@error('nombre_usuario')<div class="text-danger small">{{ $message }}</div>@enderror</div>
@if(!$cuenta)
<div class="col-md-6"><label class="form-label">Rol de la cuenta</label><select class="form-select" id="rolCuenta" name="rol" required><option value="BIBLIOTECARIO" @selected(old('rol')==='BIBLIOTECARIO')>BIBLIOTECARIO</option><option value="USUARIO" @selected(old('rol')==='USUARIO')>USUARIO</option></select>@error('rol')<div class="text-danger small">{{ $message }}</div>@enderror</div>
<div class="col-md-12" id="vinculoEmpleado"><label class="form-label">Bibliotecario existente</label><select class="form-select" name="id_bibliotecario" id="id_bibliotecario"><option value="">Selecciona un bibliotecario</option>@foreach($empleados as $empleado)<option value="{{ $empleado->id_bibliotecario }}" @selected(old('id_bibliotecario')==$empleado->id_bibliotecario)>{{ $empleado->persona?->nombre_completo }} (ID {{ $empleado->id_bibliotecario }})</option>@endforeach</select>@error('id_bibliotecario')<div class="text-danger small">{{ $message }}</div>@enderror</div>
<div class="col-md-12" id="vinculoUsuario"><label class="form-label">Lector existente</label><select class="form-select" name="id_usuario" id="id_usuario"><option value="">Selecciona un usuario</option>@foreach($lectores as $lector)<option value="{{ $lector->id_usuario }}" @selected(old('id_usuario')==$lector->id_usuario)>{{ $lector->persona?->nombre_completo }} (ID {{ $lector->id_usuario }})</option>@endforeach</select>@error('id_usuario')<div class="text-danger small">{{ $message }}</div>@enderror</div>
@else
<div class="col-md-6"><label class="form-label">Estado de la cuenta</label><select class="form-select" name="activo"><option value="1" @selected(old('activo',(int)$cuenta->activo)==1)>Activa</option><option value="0" @selected(old('activo',(int)$cuenta->activo)==0)>Desactivada</option></select></div>
@endif
<div class="col-md-6"><label class="form-label">{{ $cuenta ? 'Nueva contraseña (opcional)' : 'Contraseña inicial' }}</label><input type="password" class="form-control" name="password" minlength="12" autocomplete="new-password" @required(!$cuenta)><small class="text-muted">Mínimo 12 caracteres. Nunca se muestra al SUPERADMIN después del guardado.</small>@error('password')<div class="text-danger small">{{ $message }}</div>@enderror</div>
<div class="col-md-6"><label class="form-label">Confirmar contraseña</label><input type="password" class="form-control" name="password_confirmation" minlength="12" autocomplete="new-password" @required(!$cuenta)></div></div>
<div class="d-flex gap-2 mt-4"><button type="submit" class="btn btn-primary">Guardar cuenta</button><a class="btn btn-light" href="{{ route('cuentas.index') }}">Cancelar</a></div></form></div>
@if(!$cuenta)
<script>
document.addEventListener('DOMContentLoaded',()=>{const rol=document.getElementById('rolCuenta'), emp=document.getElementById('vinculoEmpleado'), usr=document.getElementById('vinculoUsuario'), e=document.getElementById('id_bibliotecario'),u=document.getElementById('id_usuario');function actualizar(){const isBiblio=rol.value==='BIBLIOTECARIO';emp.hidden=!isBiblio;usr.hidden=isBiblio;e.disabled=!isBiblio;u.disabled=isBiblio;}rol.addEventListener('change', actualizar);actualizar();});
</script>
@endif
@endsection
