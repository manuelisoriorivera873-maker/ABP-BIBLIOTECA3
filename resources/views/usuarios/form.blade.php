<div class="form-section">
    <h3>Datos personales</h3>

    @isset($personasDisponibles)
        <label class="form-label" for="personaExistente">Persona existente</label>
        <select class="form-select mb-3" name="id_persona" id="personaExistente">
            <option value="">Registrar una persona nueva</option>
            @foreach($personasDisponibles as $persona)
                <option value="{{ $persona->id_persona }}" @selected(old('id_persona') == $persona->id_persona)>{{ $persona->nombre_completo }} (ID {{ $persona->id_persona }})</option>
            @endforeach
        </select>
        <p class="text-muted">Selecciona una persona para conservar sus datos y sus funciones como autor o bibliotecario.</p>
    @endisset
    <div class="row g-3" id="datosPersonaNueva">
        <div class="col-md-4">
            <label class="form-label">Nombre(s) del usuario *</label>
            <input
                class="form-control"
                name="nombre"
                maxlength="80"
                value="{{ old('nombre', $usuario->persona->nombre ?? '') }}"
                required
            >
        </div>

        <div class="col-md-4">
            <label class="form-label">Apellido paterno *</label>
            <input
                class="form-control"
                name="apellido_paterno"
                maxlength="80"
                value="{{ old('apellido_paterno', $usuario->persona->apellido_paterno ?? '') }}"
                required
            >
        </div>

        <div class="col-md-4">
            <label class="form-label">Apellido materno *</label>
            <input
                class="form-control"
                name="apellido_materno"
                maxlength="80"
                value="{{ old('apellido_materno', $usuario->persona->apellido_materno ?? '') }}"
                required
            >
        </div>
    </div>
</div>

@isset($personasDisponibles)
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selector = document.getElementById('personaExistente');
            const datos = document.getElementById('datosPersonaNueva');
            function actualizarPersona() {
                datos.hidden = selector.value !== '';
                datos.querySelectorAll('input').forEach(input => {
                    input.disabled = datos.hidden;
                    input.required = !datos.hidden;
                });
            }
            selector.addEventListener('change', actualizarPersona);
            actualizarPersona();
        });
    </script>
@endisset

<div class="form-section">
    <h3>Datos del usuario de biblioteca</h3>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Teléfono del usuario *</label>
            <input
                class="form-control"
                name="telefono"
                maxlength="20"
                value="{{ old('telefono', $usuario->telefono ?? '') }}"
                required
            >
        </div>

        <div class="col-md-6">
            <label class="form-label">Estado del usuario *</label>
            <select class="form-select" name="id_estado_usuario" required>
                <option value="">Selecciona un estado</option>

                @foreach ($estados as $estado)
                    <option
                        value="{{ $estado->id_estado_usuario }}"
                        @selected(
                            (int) old('id_estado_usuario', $usuario->id_estado_usuario ?? $estadoPredeterminado ?? 0)
                            === $estado->id_estado_usuario
                        )
                    >
                        {{ $estado->nombre_estado_usuario }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-light" href="{{ route('usuarios.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar usuario</button>
</div>
