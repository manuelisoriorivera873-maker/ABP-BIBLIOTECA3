<div class="form-section">
    <h3>Datos personales del bibliotecario</h3>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Nombre(s) del bibliotecario *</label>
            <input
                class="form-control"
                name="nombre"
                maxlength="80"
                value="{{ old('nombre', $bibliotecario->persona->nombre ?? '') }}"
                required
            >
        </div>

        <div class="col-md-4">
            <label class="form-label">Apellido paterno *</label>
            <input
                class="form-control"
                name="apellido_paterno"
                maxlength="80"
                value="{{ old('apellido_paterno', $bibliotecario->persona->apellido_paterno ?? '') }}"
                required
            >
        </div>

        <div class="col-md-4">
            <label class="form-label">Apellido materno *</label>
            <input
                class="form-control"
                name="apellido_materno"
                maxlength="80"
                value="{{ old('apellido_materno', $bibliotecario->persona->apellido_materno ?? '') }}"
                required
            >
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-light" href="{{ route('bibliotecarios.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar bibliotecario</button>
</div>
