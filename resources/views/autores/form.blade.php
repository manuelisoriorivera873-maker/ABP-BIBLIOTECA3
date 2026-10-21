<div class="form-section">
    <h3>Vinculación del autor</h3>
    <p class="text-muted mb-3">Para evitar redundancia, el autor se registra a partir de una persona ya existente.</p>

    <div class="row g-3">
        <div class="col-lg-8">
            <label class="form-label">Persona que será autor *</label>
            <select class="form-select" name="id_persona" required>
                <option value="">Selecciona una persona</option>
                @foreach ($personasDisponibles as $persona)
                    <option value="{{ $persona->id_persona }}" @selected((int) old('id_persona', $autor->id_persona ?? 0) === (int) $persona->id_persona)>
                        {{ $persona->nombre_completo }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-4">
            <label class="form-label">Vista previa</label>
            <div class="preview-box">
                {{ old('id_persona') ? 'Persona seleccionada para autor' : ($autor->nombre_completo ?? 'Selecciona una persona') }}
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-light" href="{{ route('autores.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar autor</button>
</div>
