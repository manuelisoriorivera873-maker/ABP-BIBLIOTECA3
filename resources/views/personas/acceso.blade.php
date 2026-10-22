<div class="form-section mt-4">
    <h3>Asignación de rol</h3>
    <p class="text-muted">El rol se guarda en la cuenta de acceso. Los datos y movimientos históricos de esta persona se conservan.</p>

    @if($cuentasPersona->count() > 1)
        <div class="alert alert-info">
            Esta persona tiene varias cuentas. Selecciona explícitamente la que deseas administrar:
            @foreach($cuentasPersona as $opcion)
                <a class="d-block" href="{{ route('personas.edit', ['persona' => $persona, 'cuenta' => $opcion->id_cuenta]) }}">{{ $opcion->nombre_usuario }} · {{ $opcion->rol }}</a>
            @endforeach
        </div>
    @endif

    @if($cuentaPersona)
        <p><strong>Cuenta:</strong> {{ $cuentaPersona->nombre_usuario }} · <strong>Rol actual:</strong> {{ $cuentaPersona->rol }}</p>
        <input type="hidden" name="acceso[gestionar]" value="1">
        <input type="hidden" name="acceso[cuenta_id]" value="{{ $cuentaPersona->id_cuenta }}">
    @else
        <p>Esta persona todavía no tiene cuenta de acceso.</p>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="crearAccesoPersona" name="acceso[gestionar]" value="1" @checked(old('acceso.gestionar'))>
            <label class="form-check-label" for="crearAccesoPersona">Crear cuenta con credenciales válidas</label>
        </div>
    @endif

    <fieldset id="camposAccesoPersona" @disabled(!$cuentaPersona && !old('acceso.gestionar'))>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="rolPersona">Rol de la cuenta</label>
                <select class="form-select" id="rolPersona" name="acceso[rol]" required>
                    @foreach(['USUARIO' => 'USUARIO — Usuario lector', 'BIBLIOTECARIO' => 'BIBLIOTECARIO — Bibliotecario', 'SUPERADMIN' => 'SUPERADMIN — Superadministrador'] as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(old('acceso.rol', $cuentaPersona?->rol ?? 'USUARIO') === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                @error('acceso.rol')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="estadoAccesoPersona">Estado del acceso</label>
                <select class="form-select" id="estadoAccesoPersona" name="acceso[activo]" required>
                    <option value="1" @selected((string) old('acceso.activo', (int) ($cuentaPersona?->activo ?? true)) === '1')>Activo</option>
                    <option value="0" @selected((string) old('acceso.activo', (int) ($cuentaPersona?->activo ?? true)) === '0')>Inactivo</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="telefonoAccesoPersona">Teléfono para crear un lector</label>
                <input class="form-control" id="telefonoAccesoPersona" name="acceso[telefono]" type="tel" minlength="8" maxlength="20" pattern="[0-9 +\(\)\-]+" value="{{ old('acceso.telefono') }}">
                <small class="text-muted">Necesario al asignar USUARIO si la persona todavía no es lectora.</small>
            </div>
            @unless($cuentaPersona)
                <div class="col-md-6">
                    <label class="form-label" for="usuarioAccesoPersona">Nombre de usuario</label>
                    <input class="form-control" id="usuarioAccesoPersona" name="acceso[nombre_usuario]" value="{{ old('acceso.nombre_usuario') }}" minlength="4" maxlength="60" pattern="[A-Za-z0-9._\-]+" autocomplete="off" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="claveAccesoPersona">Contraseña (mínimo 12 caracteres)</label>
                    <input class="form-control" id="claveAccesoPersona" name="acceso[password]" type="password" minlength="12" autocomplete="new-password" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="confirmarAccesoPersona">Confirmar contraseña</label>
                    <input class="form-control" id="confirmarAccesoPersona" name="acceso[password_confirmation]" type="password" minlength="12" autocomplete="new-password" required>
                </div>
            @endunless
        </div>
    </fieldset>

    <p class="text-muted mt-3">La cuenta hereda automáticamente la matriz vigente del rol seleccionado. No se asignan permisos individuales.</p>
    @if($cuentaPersona)
        <a class="btn btn-outline-primary" href="{{ route('cuentas.edit', $cuentaPersona) }}">Administrar credenciales de la cuenta</a>
        @if($cuentaPersona->rol !== 'SUPERADMIN')
            <a class="btn btn-outline-primary" href="{{ route('cuentas.roles.permisos', $cuentaPersona->rol) }}">Consultar matriz del rol</a>
        @endif
    @endif
</div>

@unless($cuentaPersona)
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const crear = document.getElementById('crearAccesoPersona');
            const campos = document.getElementById('camposAccesoPersona');
            crear.addEventListener('change', () => { campos.disabled = !crear.checked; });
        });
    </script>
@endunless
