<div class="form-section">
    <h3>{{ $c['titulo'] }}</h3>

    <div class="row g-3">
        <div class="col-md-{{ $c['descripcion'] ? '5' : '12' }}">
            <label class="form-label">{{ $c['etiqueta'] }} *</label>
            <input
                class="form-control"
                name="{{ $c['campo'] }}"
                maxlength="{{ $c['max'] }}"
                value="{{ old($c['campo'], $registro->{$c['campo']} ?? '') }}"
                required
            >
        </div>

        @if ($c['descripcion'])
            <div class="col-md-7">
                <label class="form-label">Descripción de la ubicación</label>
                <input
                    class="form-control"
                    name="descripcion"
                    maxlength="255"
                    value="{{ old('descripcion', $registro->descripcion ?? '') }}"
                    placeholder="Ejemplo: segundo nivel, estante cercano a la ventana"
                >
            </div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-light" href="{{ route('catalogos.index', $catalogo) }}">
        Cancelar
    </a>
    <button class="btn btn-primary" type="submit">Guardar registro</button>
</div>
