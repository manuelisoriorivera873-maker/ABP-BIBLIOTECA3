<?php

namespace App\Http\Controllers;

use App\Models\Devolucion;
use App\Models\Ejemplar;
use App\Models\EstadoEjemplar;
use App\Models\Prestamo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DevolucionController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));

        $pendientes = Prestamo::with([
            'usuario.persona',
            'bibliotecario.persona',
            'detalles.ejemplar.libro',
            'detalles.devolucionesDetalle',
        ])
            ->visiblesPara($request->user())
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($subquery) use ($buscar): void {
                    if (ctype_digit($buscar)) {
                        $subquery->orWhere('id_prestamo', (int) $buscar);
                    }

                    $subquery
                        ->orWhereHas('usuario.persona', function ($persona) use ($buscar): void {
                            $persona
                                ->where('nombre', 'like', "%{$buscar}%")
                                ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                                ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('detalles.ejemplar', function ($ejemplar) use ($buscar): void {
                            $ejemplar
                                ->where('codigo_inventario', 'like', "%{$buscar}%")
                                ->orWhereHas('libro', function ($libro) use ($buscar): void {
                                    $libro->where('titulo', 'like', "%{$buscar}%");
                                });
                        });
                });
            })
            ->whereHas('detalles', fn ($detalle) => $detalle->whereDoesntHave('devolucionesDetalle'))
            ->orderBy('id_prestamo')
            ->paginate(8, ['*'], 'pendientes_page')->withQueryString()
            ->through(function (Prestamo $prestamo): Prestamo {
                $pendientes = $prestamo->detalles->filter(fn ($detalle) => $detalle->devolucionesDetalle->isEmpty())->values();
                $prestamo->setRelation('detalles_pendientes', $pendientes);

                return $prestamo;
            });

        $devoluciones = Devolucion::with([
            'prestamo.usuario.persona',
            'prestamo.bibliotecario.persona',
            'detalles.detallePrestamo.ejemplar.libro',
        ])
            ->whereHas('prestamo', fn ($prestamo) => $prestamo->visiblesPara($request->user()))
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($subquery) use ($buscar): void {
                    if (ctype_digit($buscar)) {
                        $subquery
                            ->orWhere('id_devolucion', (int) $buscar)
                            ->orWhere('id_prestamo', (int) $buscar);
                    }

                    $subquery
                        ->orWhereHas('prestamo.usuario.persona', function ($persona) use ($buscar): void {
                            $persona
                                ->where('nombre', 'like', "%{$buscar}%")
                                ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                                ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('detalles.detallePrestamo.ejemplar', function ($ejemplar) use ($buscar): void {
                            $ejemplar
                                ->where('codigo_inventario', 'like', "%{$buscar}%")
                                ->orWhereHas('libro', function ($libro) use ($buscar): void {
                                    $libro->where('titulo', 'like', "%{$buscar}%");
                                });
                        });
                });
            })
            ->orderBy('id_devolucion')
            ->paginate(10, ['*'], 'historial_page')
            ->withQueryString();

        return view('devoluciones.index', compact('pendientes', 'devoluciones', 'buscar'));
    }

    public function registrar(Request $request, Prestamo $prestamo)
    {
        $data = $request->validate([
            'fecha_devolucion' => ['required', 'date'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*' => ['integer'],
        ], [
            'detalles.required' => 'Selecciona al menos un libro o ejemplar para registrar la devolución.',
        ]);

        $fechaDevolucion = Carbon::parse($data['fecha_devolucion']);
        $seleccionados = collect($data['detalles'])->map(fn ($id) => (int) $id)->unique()->values()->all();

        if ($fechaDevolucion->lt($prestamo->fecha_prestamo)) {
            throw ValidationException::withMessages([
                'fecha_devolucion' => 'La fecha de devolución no puede ser anterior a la fecha de préstamo.',
            ]);
        }

        DB::transaction(function () use ($fechaDevolucion, $prestamo, $seleccionados): void {
            $prestamoActual = Prestamo::with(['detalles.devolucionesDetalle', 'detalles.ejemplar'])
                ->whereKey($prestamo->id_prestamo)
                ->lockForUpdate()
                ->firstOrFail();

            $detallesPendientes = $prestamoActual->detalles
                ->filter(fn ($detalle) => $detalle->devolucionesDetalle->isEmpty())
                ->keyBy('id_detalle');

            foreach ($seleccionados as $idDetalle) {
                if (! $detallesPendientes->has($idDetalle)) {
                    throw ValidationException::withMessages([
                        'detalles' => 'Uno de los ejemplares seleccionados ya fue devuelto o no pertenece al préstamo.',
                    ]);
                }
            }

            $devolucion = Devolucion::create([
                'id_prestamo' => $prestamoActual->id_prestamo,
                'fecha_devolucion' => $fechaDevolucion->toDateString(),
            ]);

            $estadoDisponible = EstadoEjemplar::where('nombre_estado', 'DISPONIBLE')->first();

            if (! $estadoDisponible) {
                throw ValidationException::withMessages([
                    'fecha_devolucion' => 'No existe el estado DISPONIBLE en el catálogo de ejemplares.',
                ]);
            }

            $idsEjemplares = [];
            foreach ($seleccionados as $idDetalle) {
                $detalle = $detallesPendientes->get($idDetalle);
                $devolucion->detalles()->create([
                    'id_detalle_prestamo' => $detalle->id_detalle,
                    'observaciones' => null,
                ]);
                $idsEjemplares[] = (int) $detalle->id_ejemplar;
            }

            Ejemplar::whereIn('id_ejemplar', $idsEjemplares)->update([
                'id_estado_ejemplar' => $estadoDisponible->id_estado_ejemplar,
            ]);
        });

        return redirect()
            ->route('devoluciones.index')
            ->with('success', 'Devolución registrada correctamente. Puedes devolver uno o varios libros por movimiento.');
    }

    public function edit(Devolucion $devolucion)
    {
        $devolucion->load([
            'prestamo.usuario.persona',
            'detalles.detallePrestamo.ejemplar.libro',
        ]);

        return view('devoluciones.edit', compact('devolucion'));
    }

    public function update(Request $request, Devolucion $devolucion)
    {
        $data = $request->validate([
            'fecha_devolucion' => ['required', 'date'],
        ]);

        $devolucion->load('prestamo');
        $fechaDevolucion = Carbon::parse($data['fecha_devolucion']);

        if ($devolucion->prestamo && $fechaDevolucion->lt($devolucion->prestamo->fecha_prestamo)) {
            throw ValidationException::withMessages([
                'fecha_devolucion' => 'La fecha de devolución no puede ser anterior a la fecha de préstamo.',
            ]);
        }

        $devolucion->update([
            'fecha_devolucion' => $fechaDevolucion->toDateString(),
        ]);

        return redirect()->route('devoluciones.index')->with('success', 'Fecha de devolución actualizada correctamente.');
    }

    public function destroy(Devolucion $devolucion)
    {
        $devolucion->load('detalles.detallePrestamo.ejemplar', 'prestamo');

        $idsEjemplares = $devolucion->detalles
            ->map(fn ($detalle) => (int) optional($detalle->detallePrestamo)->id_ejemplar)
            ->filter()
            ->values()
            ->all();

        $hayPrestamoPosterior = DB::table('detalle_prestamo')
            ->join('prestamos', 'prestamos.id_prestamo', '=', 'detalle_prestamo.id_prestamo')
            ->whereIn('detalle_prestamo.id_ejemplar', $idsEjemplares)
            ->where('prestamos.id_prestamo', '>', $devolucion->id_prestamo)
            ->exists();

        if ($hayPrestamoPosterior) {
            return back()->with('error', 'No se puede anular esta devolución porque uno de sus ejemplares ya aparece en un préstamo posterior.');
        }

        DB::transaction(function () use ($devolucion, $idsEjemplares): void {
            $devolucion->detalles()->delete();
            $devolucion->delete();

            $estadoPrestado = EstadoEjemplar::where('nombre_estado', 'PRESTADO')->first();
            if ($estadoPrestado && $idsEjemplares !== []) {
                Ejemplar::whereIn('id_ejemplar', $idsEjemplares)->update([
                    'id_estado_ejemplar' => $estadoPrestado->id_estado_ejemplar,
                ]);
            }
        });

        return redirect()->route('devoluciones.index')->with('success', 'Devolución anulada. Los ejemplares volvieron al estado PRESTADO.');
    }
}
