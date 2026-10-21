@php
    $seleccionAutores = collect(old('autores', isset($libro) ? $libro->autores->pluck('id_autor')->all() : []))->map(fn ($valor) => (int) $valor)->all();
    $seleccionCategorias = collect(old('categorias', isset($libro) ? $libro->categorias->pluck('id_categoria')->all() : []))->map(fn ($valor) => (int) $valor)->all();
    $seleccionEditoriales = collect(old('editoriales', isset($libro) ? $libro->editoriales->pluck('id_editorial')->all() : []))->map(fn ($valor) => (int) $valor)->all();
@endphp

<div class="form-section">
    <h3>Datos del libro</h3>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">ISBN del libro *</label>
            <input class="form-control" name="isbn" maxlength="20" value="{{ old('isbn', $libro->isbn ?? '') }}" required>
        </div>
        <div class="col-md-8">
            <label class="form-label">Título del libro *</label>
            <input class="form-control" name="titulo" maxlength="200" value="{{ old('titulo', $libro->titulo ?? '') }}" required>
        </div>
    </div>
</div>

<div class="form-section">
    <div class="section-title-row">
        <div>
            <h3>Autor(es) del libro</h3>
            <p class="mb-0 text-muted small">Selecciona uno o varios autores. Cada autor proviene de la tabla de personas.</p>
        </div>
        @if (auth()->user()->puede('autores', 'crear'))
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAutor">+ Vincular autor desde personas</button>
        @endif
    </div>

    <input class="form-control mb-2" type="search" data-filter-select="autoresSelect" placeholder="Filtrar autores por nombre o apellidos">

    <select class="form-select" id="autoresSelect" name="autores[]" multiple size="6" required>
        @foreach ($autores as $autor)
            <option value="{{ $autor->id_autor }}" @selected(in_array($autor->id_autor, $seleccionAutores, true))>
                {{ $autor->nombre_completo }}
            </option>
        @endforeach
    </select>

    <div class="form-text">Para elegir varios registros mantén presionada la tecla Ctrl mientras haces clic.</div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="form-section h-100">
            <div class="section-title-row">
                <div>
                    <h3>Categoría(s) del libro</h3>
                    <p class="mb-0 text-muted small">Selecciona una o varias categorías.</p>
                </div>
                @if (auth()->user()->puede('categorias', 'crear'))
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCategoria">+ Nueva categoría</button>
                @endif
            </div>
            <select class="form-select" id="categoriasSelect" name="categorias[]" multiple size="5" required>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id_categoria }}" @selected(in_array($categoria->id_categoria, $seleccionCategorias, true))>
                        {{ $categoria->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="form-section h-100">
            <div class="section-title-row">
                <div>
                    <h3>Editorial(es) del libro</h3>
                    <p class="mb-0 text-muted small">Selecciona una o varias editoriales.</p>
                </div>
                @if (auth()->user()->puede('editoriales', 'crear'))
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditorial">+ Nueva editorial</button>
                @endif
            </div>
            <select class="form-select" id="editorialesSelect" name="editoriales[]" multiple size="5" required>
                @foreach ($editoriales as $editorial)
                    <option value="{{ $editorial->id_editorial }}" @selected(in_array($editorial->id_editorial, $seleccionEditoriales, true))>
                        {{ $editorial->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-light" href="{{ route('libros.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar libro</button>
</div>

<div class="modal fade" id="modalAutor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vincular autor desde Personas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Persona disponible *</label>
                <select class="form-select" id="qaPersona">
                    <option value="">Selecciona una persona</option>
                    @foreach ($personasSinAutor as $persona)
                        <option value="{{ $persona->id_persona }}">{{ $persona->nombre_completo }}</option>
                    @endforeach
                </select>
                <div class="small text-danger mt-2" id="qaError"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="button" id="guardarAutorRapido" data-url="{{ route('rapido.autores') }}">Guardar y seleccionar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar categoría rápidamente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Nombre de la categoría</label>
                <input class="form-control" id="qcNombre">
                <div class="small text-danger mt-2" id="qcError"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="button" data-quick-catalog="categorias" data-url="{{ route('rapido.catalogos', 'categorias') }}" data-input="qcNombre" data-select="categoriasSelect" data-error="qcError" data-modal="modalCategoria">Guardar y seleccionar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditorial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar editorial rápidamente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Nombre de la editorial</label>
                <input class="form-control" id="qeNombre">
                <div class="small text-danger mt-2" id="qeError"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="button" data-quick-catalog="editoriales" data-url="{{ route('rapido.catalogos', 'editoriales') }}" data-input="qeNombre" data-select="editorialesSelect" data-error="qeError" data-modal="modalEditorial">Guardar y seleccionar</button>
            </div>
        </div>
    </div>
</div>
