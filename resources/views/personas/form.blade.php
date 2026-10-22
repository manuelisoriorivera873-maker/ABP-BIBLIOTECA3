<div class="form-section">
    <h3>Datos de la persona</h3>
    <p class="text-muted mb-3">
        Captura los datos generales de la persona. Después puede vincularse como usuario o bibliotecario.
    </p>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Nombre(s) *</label>
            <input
                class="form-control"
                name="nombre"
                maxlength="80"
                value="{{ old('nombre', $persona->nombre ?? '') }}"
                required
            >
        </div>

        <div class="col-md-4">
            <label class="form-label">Apellido paterno *</label>
            <input
                class="form-control"
                name="apellido_paterno"
                maxlength="80"
                value="{{ old('apellido_paterno', $persona->apellido_paterno ?? '') }}"
                required
            >
        </div>

        <div class="col-md-4">
            <label class="form-label">Apellido materno *</label>
            <input
                class="form-control"
                name="apellido_materno"
                maxlength="80"
                value="{{ old('apellido_materno', $persona->apellido_materno ?? '') }}"
                required
            >
        </div>
    </div>
</div>

@if(isset($persona) && auth()->user()->rol === 'SUPERADMIN')
    @include('personas.acceso')
@endif

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-light" href="{{ route('personas.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar persona</button>
</div>
