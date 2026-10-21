<div class="form-section">
    <h3>Identificación del ejemplar</h3>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Código de inventario</label>
            <input
                class="form-control"
                name="codigo_inventario"
                maxlength="40"
                value="{{ old('codigo_inventario', $ejemplar->codigo_inventario ?? '') }}"
                placeholder="Déjalo vacío para generarlo automáticamente"
            >
            <div class="form-text">
                Si lo dejas vacío, el sistema genera un código como EJ-00001.
            </div>
        </div>

        @unless (isset($ejemplar))
            <div class="col-md-4">
                <label class="form-label">Cantidad de ejemplares *</label>
                <input
                    class="form-control"
                    type="number"
                    name="cantidad"
                    min="1"
                    max="20"
                    value="{{ old('cantidad', 1) }}"
                    required
                >
                <div class="form-text">
                    Puedes registrar hasta 20 copias iguales en una sola operación.
                </div>
            </div>
        @endunless

        <div class="col-md-{{ isset($ejemplar) ? '8' : '4' }}">
            <label class="form-label">Libro al que pertenece el ejemplar *</label>
            <input
                class="form-control mb-2"
                type="search"
                data-filter-select="libroSelect"
                placeholder="Filtrar por título o ISBN"
            >

            <select class="form-select" id="libroSelect" name="id_libro" size="6" required>
                @foreach ($libros as $libro)
                    <option
                        value="{{ $libro->id_libro }}"
                        @selected(
                            (int) old('id_libro', $ejemplar->id_libro ?? 0)
                            === $libro->id_libro
                        )
                    >
                        {{ $libro->titulo }} · ISBN {{ $libro->isbn }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="form-section">
    <h3>Ubicación y disponibilidad</h3>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Ubicación física del ejemplar *</label>
            <select class="form-select" name="id_ubicacion" required>
                <option value="">Selecciona una ubicación</option>

                @foreach ($ubicaciones as $ubicacion)
                    <option
                        value="{{ $ubicacion->id_ubicacion }}"
                        @selected(
                            (int) old('id_ubicacion', $ejemplar->id_ubicacion ?? 0)
                            === $ubicacion->id_ubicacion
                        )
                    >
                        {{ $ubicacion->nombre_ubicacion }}
                        @if ($ubicacion->descripcion)
                            · {{ $ubicacion->descripcion }}
                        @endif
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Estado actual del ejemplar *</label>
            <select class="form-select" name="id_estado_ejemplar" required>
                <option value="">Selecciona un estado</option>

                @foreach ($estados as $estado)
                    <option
                        value="{{ $estado->id_estado_ejemplar }}"
                        @selected(
                            (int) old('id_estado_ejemplar', $ejemplar->id_estado_ejemplar ?? $estadoPredeterminado ?? 0)
                            === $estado->id_estado_ejemplar
                        )
                    >
                        {{ $estado->nombre_estado }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-light" href="{{ route('ejemplares.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar ejemplar</button>
</div>
