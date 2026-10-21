<?php

namespace App\Http\Controllers;

use App\Models\Bibliotecario;
use App\Models\DetallePrestamo;
use App\Models\Ejemplar;
use App\Models\EstadoEjemplar;
use App\Models\Prestamo;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrestamoController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));
        $filtro = (string) $request->input('estado', 'todos');
        $hoy = Carbon::today();

        $prestamosColeccion = Prestamo::with([
            'usuario.persona',
            'bibliotecario.persona',
            'detalles.ejemplar.libro',
            'detalles.devolucionesDetalle',
            'devolucion',
        ])
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($subquery) use ($buscar): void {
                    $subquery
                        ->whereHas('usuario.persona', function ($persona) use ($buscar): void {
                            $persona
                                ->where('nombre', 'like', "%{$buscar}%")
                                ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                                ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('detalles.ejemplar', function ($ejemplar) use ($buscar): void {
                            $ejemplar
                                ->where('codigo_inventario', 'like', "%{$buscar}%")
                                ->orWhereHas('libro', function ($libro) use ($buscar): void {
                                    $libro
                                        ->where('titulo', 'like', "%{$buscar}%")
                                        ->orWhere('isbn', 'like', "%{$buscar}%");
                                });
                        });
                });
            })
            ->orderByDesc('id_prestamo')
            ->get()
            ->map(function (Prestamo $prestamo) use ($hoy): Prestamo {
                $pendientes = $prestamo->detalles->filter(fn ($detalle) => $detalle->devolucionesDetalle->isEmpty())->count();
                $estaDevuelto = $prestamo->detalles->isNotEmpty() && $pendientes === 0;
                $atrasado = !$estaDevuelto && $prestamo->fecha_limite->lt($hoy);
                $prestamo->pendientes = $pendientes;
                $prestamo->esta_devuelto = $estaDevuelto;
                $prestamo->esta_atrasado = $atrasado;
                return $prestamo;
            })
            ->filter(function (Prestamo $prestamo) use ($filtro): bool {
                return match ($filtro) {
                    'devueltos' => $prestamo->esta_devuelto,
                    'activos' => !$prestamo->esta_devuelto && !$prestamo->esta_atrasado,
                    'atrasados' => $prestamo->esta_atrasado,
                    default => true,
                };
            })
            ->values();

        $prestamos = $this->paginadorColeccion($prestamosColeccion, 12, $request);

        return view('prestamos.index', compact('prestamos', 'buscar', 'filtro', 'hoy'));
    }

    public function create()
    {
        return view('prestamos.create', $this->datosFormulario());
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($data): void {
            $this->validarUsuarioActivo((int) $data['id_usuario']);

            $ejemplares = $this->obtenerEjemplaresDisponibles($data['ejemplares']);

            $prestamo = Prestamo::create([
                'id_usuario' => $data['id_usuario'],
                'fecha_prestamo' => $data['fecha_prestamo'],
                'fecha_limite' => $data['fecha_limite'],
                'id_bibliotecario' => $data['id_bibliotecario'],
            ]);

            foreach ($ejemplares as $ejemplar) {
                DetallePrestamo::create([
                    'id_prestamo' => $prestamo->id_prestamo,
                    'id_ejemplar' => $ejemplar->id_ejemplar,
                ]);
            }

            $this->cambiarEstadoEjemplares($ejemplares->pluck('id_ejemplar')->all(), 'PRESTADO');
        });

        return redirect()->route('prestamos.index')->with('success', 'Préstamo registrado correctamente.');
    }

    public function edit(Prestamo $prestamo)
    {
        if ($prestamo->devoluciones()->exists()) {
            return redirect()->route('prestamos.index')->with('error', 'Un préstamo con devoluciones registradas ya no puede modificarse.');
        }

        $prestamo->load('detalles');

        return view('prestamos.edit', array_merge($this->datosFormulario($prestamo), compact('prestamo')));
    }

    public function update(Request $request, Prestamo $prestamo)
    {
        if ($prestamo->devoluciones()->exists()) {
            return redirect()->route('prestamos.index')->with('error', 'Un préstamo con devoluciones registradas ya no puede modificarse.');
        }

        $data = $this->validar($request);

        DB::transaction(function () use ($data, $prestamo): void {
            $this->validarUsuarioActivo((int) $data['id_usuario']);

            $idsActuales = $prestamo->detalles()->pluck('id_ejemplar')->map(fn ($id) => (int) $id)->all();
            $idsNuevos = array_map('intval', $data['ejemplares']);
            $idsAgregados = array_values(array_diff($idsNuevos, $idsActuales));
            $idsEliminados = array_values(array_diff($idsActuales, $idsNuevos));

            if ($idsAgregados !== []) {
                $this->obtenerEjemplaresDisponibles($idsAgregados);
            }

            $prestamo->update([
                'id_usuario' => $data['id_usuario'],
                'fecha_prestamo' => $data['fecha_prestamo'],
                'fecha_limite' => $data['fecha_limite'],
                'id_bibliotecario' => $data['id_bibliotecario'],
            ]);

            if ($idsEliminados !== []) {
                $prestamo->detalles()->whereIn('id_ejemplar', $idsEliminados)->delete();
                $this->cambiarEstadoEjemplares($idsEliminados, 'DISPONIBLE');
            }

            foreach ($idsAgregados as $idEjemplar) {
                DetallePrestamo::create([
                    'id_prestamo' => $prestamo->id_prestamo,
                    'id_ejemplar' => $idEjemplar,
                ]);
            }

            if ($idsAgregados !== []) {
                $this->cambiarEstadoEjemplares($idsAgregados, 'PRESTADO');
            }
        });

        return redirect()->route('prestamos.index')->with('success', 'Préstamo actualizado correctamente.');
    }

    public function destroy(Prestamo $prestamo)
    {
        DB::transaction(function () use ($prestamo): void {
            $idsEjemplares = $prestamo->detalles()->pluck('id_ejemplar')->map(fn ($id) => (int) $id)->all();
            $prestamo->devoluciones()->each(function ($devolucion): void {
                $devolucion->detalles()->delete();
            });
            $prestamo->devoluciones()->delete();
            $prestamo->delete();
            $this->cambiarEstadoEjemplares($idsEjemplares, 'DISPONIBLE');
        });

        return back()->with('success', 'Préstamo eliminado correctamente.');
    }

    private function datosFormulario(?Prestamo $prestamo = null): array
    {
        $idUsuarioActual = $prestamo?->id_usuario;

        $usuarios = Usuario::with(['persona', 'estado'])
            ->where(function ($query) use ($idUsuarioActual): void {
                $query->whereHas('estado', function ($estado): void {
                    $estado->where('nombre_estado_usuario', 'ACTIVO');
                });

                if ($idUsuarioActual) {
                    $query->orWhere('id_usuario', $idUsuarioActual);
                }
            })
            ->get()
            ->sortBy(fn ($usuario) => $usuario->persona?->nombre_completo)
            ->values();

        $bibliotecarios = Bibliotecario::with('persona')
            ->get()
            ->sortBy(fn ($bibliotecario) => $bibliotecario->persona?->nombre_completo)
            ->values();

        $idsActuales = $prestamo ? $prestamo->detalles()->pluck('id_ejemplar')->map(fn ($id) => (int) $id)->all() : [];

        $estadoDisponible = EstadoEjemplar::where('nombre_estado', 'DISPONIBLE')->first();

        $ejemplares = Ejemplar::with(['libro', 'ubicacion', 'estado'])
            ->where(function ($query) use ($estadoDisponible, $idsActuales): void {
                if ($estadoDisponible) {
                    $query->where('id_estado_ejemplar', $estadoDisponible->id_estado_ejemplar);
                } elseif ($idsActuales === []) {
                    $query->whereRaw('1 = 0');
                }

                if ($idsActuales !== []) {
                    $query->orWhereIn('id_ejemplar', $idsActuales);
                }
            })
            ->orderBy('codigo_inventario')
            ->get();

        return compact('usuarios', 'bibliotecarios', 'ejemplares', 'idsActuales');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'id_usuario' => ['required', 'integer', 'exists:usuarios,id_usuario'],
            'id_bibliotecario' => ['required', 'integer', 'exists:bibliotecarios,id_bibliotecario'],
            'fecha_prestamo' => ['required', 'date'],
            'fecha_limite' => ['required', 'date', 'after_or_equal:fecha_prestamo'],
            'ejemplares' => ['required', 'array', 'min:1'],
            'ejemplares.*' => ['integer', 'distinct', 'exists:ejemplares,id_ejemplar'],
        ], [
            'ejemplares.required' => 'Selecciona al menos un ejemplar.',
            'ejemplares.min' => 'Selecciona al menos un ejemplar.',
            'fecha_limite.after_or_equal' => 'La fecha límite no puede ser anterior a la fecha de préstamo.',
        ]);
    }

    private function validarUsuarioActivo(int $idUsuario): void
    {
        $usuario = Usuario::with('estado')->findOrFail($idUsuario);

        if (!$usuario->estado || $usuario->estado->nombre_estado_usuario !== 'ACTIVO') {
            throw ValidationException::withMessages([
                'id_usuario' => 'Solo se pueden registrar préstamos a usuarios con estado ACTIVO.',
            ]);
        }
    }

    private function obtenerEjemplaresDisponibles(array $idsEjemplares)
    {
        $ids = array_map('intval', $idsEjemplares);
        $estadoDisponible = EstadoEjemplar::where('nombre_estado', 'DISPONIBLE')->first();

        if (!$estadoDisponible) {
            throw ValidationException::withMessages([
                'ejemplares' => 'No existe el estado DISPONIBLE en el catálogo de ejemplares.',
            ]);
        }

        $ejemplares = Ejemplar::whereIn('id_ejemplar', $ids)
            ->where('id_estado_ejemplar', $estadoDisponible->id_estado_ejemplar)
            ->lockForUpdate()
            ->get();

        if ($ejemplares->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'ejemplares' => 'Uno o más ejemplares seleccionados ya no están disponibles.',
            ]);
        }

        return $ejemplares;
    }

    private function cambiarEstadoEjemplares(array $idsEjemplares, string $nombreEstado): void
    {
        if ($idsEjemplares === []) {
            return;
        }

        $estado = EstadoEjemplar::where('nombre_estado', $nombreEstado)->first();

        if (!$estado) {
            throw ValidationException::withMessages([
                'ejemplares' => "No existe el estado {$nombreEstado} en el catálogo de ejemplares.",
            ]);
        }

        Ejemplar::whereIn('id_ejemplar', $idsEjemplares)->update([
            'id_estado_ejemplar' => $estado->id_estado_ejemplar,
        ]);
    }

    private function paginadorColeccion(Collection $items, int $porPagina, Request $request): LengthAwarePaginator
    {
        $pageName = 'page';
        $pagina = LengthAwarePaginator::resolveCurrentPage($pageName);
        $total = $items->count();
        $resultados = $items->slice(($pagina - 1) * $porPagina, $porPagina)->values();

        return new LengthAwarePaginator($resultados, $total, $porPagina, $pagina, [
            'path' => $request->url(),
            'query' => $request->query(),
            'pageName' => $pageName,
        ]);
    }
}
