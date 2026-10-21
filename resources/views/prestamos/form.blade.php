@php
    $seleccionEjemplares = collect(
        old('ejemplares', $idsActuales ?? [])
    )
        ->map(fn ($valor) => (int) $valor)
        ->all();
@endphp

<div class="form-section">
    <h3>Datos generales del préstamo</h3>

    <div class="row g-3">
        <div class="col-lg-6">
            <label class="form-label">Usuario que recibe el préstamo *</label>
            <input
                class="form-control mb-2"
                type="search"
                data-filter-select="usuarioSelect"
                placeholder="Escribe un nombre o teléfono para filtrar"
            >

            <select
                class="form-select"
                id="usuarioSelect"
                name="id_usuario"
                size="6"
                required
            >
                @foreach ($usuarios as $usuarioRegistro)
                    <option
                        value="{{ $usuarioRegistro->id_usuario }}"
                        @selected(
                            (int) old('id_usuario', $prestamo->id_usuario ?? 0)
                            === $usuarioRegistro->id_usuario
                        )
                    >
                        {{ $usuarioRegistro->persona?->nombre_completo }}
                        · {{ $usuarioRegistro->telefono }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-6">
            <label class="form-label">Bibliotecario responsable *</label>
            <input
                class="form-control mb-2"
                type="search"
                data-filter-select="bibliotecarioSelect"
                placeholder="Escribe un nombre para filtrar"
            >

            <select
                class="form-select"
                id="bibliotecarioSelect"
                name="id_bibliotecario"
                size="6"
                required
            >
                @foreach ($bibliotecarios as $bibliotecarioRegistro)
                    <option
                        value="{{ $bibliotecarioRegistro->id_bibliotecario }}"
                        @selected(
                            (int) old('id_bibliotecario', $prestamo->id_bibliotecario ?? 0)
                            === $bibliotecarioRegistro->id_bibliotecario
                        )
                    >
                        {{ $bibliotecarioRegistro->persona?->nombre_completo }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Fecha de préstamo *</label>
            <input
                class="form-control"
                type="date"
                name="fecha_prestamo"
                value="{{ old('fecha_prestamo', isset($prestamo) ? $prestamo->fecha_prestamo->format('Y-m-d') : now()->toDateString()) }}"
                required
            >
        </div>

        <div class="col-md-6">
            <label class="form-label">Fecha límite de devolución *</label>
            <input
                class="form-control"
                type="date"
                name="fecha_limite"
                value="{{ old('fecha_limite', isset($prestamo) ? $prestamo->fecha_limite->format('Y-m-d') : now()->addDays(7)->toDateString()) }}"
                required
            >
        </div>
    </div>
</div>

<div class="form-section">
    <div class="section-title-row">
        <div>
            <h3>Ejemplares del préstamo</h3>
            <p class="mb-0 text-muted small">
                Solo aparecen ejemplares disponibles y, al editar, los que ya pertenecen al préstamo.
            </p>
        </div>

        <span class="badge text-bg-success">
            {{ $ejemplares->count() }} opciones
        </span>
    </div>

    <input
        class="form-control mb-3"
        id="buscarEjemplar"
        type="search"
        placeholder="Buscar por código de inventario, título o ubicación"
    >

    <div class="loan-picker" id="listaEjemplares">
        @forelse ($ejemplares as $ejemplarRegistro)
            @php
                $textoBusqueda = mb_strtolower(
                    $ejemplarRegistro->codigo_inventario . ' ' .
                    ($ejemplarRegistro->libro?->titulo ?? '') . ' ' .
                    ($ejemplarRegistro->ubicacion?->nombre_ubicacion ?? '')
                );

                $esActual = in_array(
                    $ejemplarRegistro->id_ejemplar,
                    $idsActuales ?? [],
                    true
                );
            @endphp

            <label class="loan-item" data-search="{{ $textoBusqueda }}">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="ejemplares[]"
                    value="{{ $ejemplarRegistro->id_ejemplar }}"
                    @checked(in_array($ejemplarRegistro->id_ejemplar, $seleccionEjemplares, true))
                >

                <span>
                    <strong>{{ $ejemplarRegistro->codigo_inventario }}</strong>
                    <small>{{ $ejemplarRegistro->libro?->titulo }}</small>
                    <em>
                        {{ $ejemplarRegistro->ubicacion?->nombre_ubicacion }}
                        @if ($esActual)
                            · Ya incluido en este préstamo
                        @endif
                    </em>
                </span>
            </label>
        @empty
            <div class="empty-state">
                No hay ejemplares disponibles para prestar.
            </div>
        @endforelse
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-light" href="{{ route('prestamos.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit" @disabled($ejemplares->isEmpty())>
        {{ isset($prestamo) ? 'Actualizar préstamo' : 'Registrar préstamo' }}
    </button>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('buscarEjemplar');
            const items = Array.from(
                document.querySelectorAll('#listaEjemplares .loan-item')
            );

            if (!input) {
                return;
            }

            input.addEventListener('input', function () {
                const value = input.value.toLowerCase().trim();

                items.forEach(function (item) {
                    item.style.display = item.dataset.search.includes(value)
                        ? ''
                        : 'none';
                });
            });
        });
    </script>
@endpush
