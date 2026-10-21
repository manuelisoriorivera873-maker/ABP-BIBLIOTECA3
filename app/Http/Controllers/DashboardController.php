<?php

namespace App\Http\Controllers;

use App\Models\Bibliotecario;
use App\Models\Devolucion;
use App\Models\Ejemplar;
use App\Models\Libro;
use App\Models\Persona;
use App\Models\Prestamo;
use App\Models\Usuario;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $cuenta = auth()->user();
        if ($cuenta->rol === 'BIBLIOTECARIO') {
            return view('dashboard.personal', [
                'modulos' => collect(\App\Support\PermisosBiblioteca::MODULOS)
                    ->filter(fn ($etiqueta, $modulo) => $cuenta->puede($modulo, 'ver')),
            ]);
        }

        $hoy = Carbon::today();

        $prestamosAnalisis = Prestamo::with(['detalles.devolucionesDetalle'])->get()->map(function (Prestamo $prestamo) use ($hoy): Prestamo {
            $pendientes = $prestamo->detalles->filter(fn ($detalle) => $detalle->devolucionesDetalle->isEmpty())->count();
            $prestamo->esta_devuelto = $prestamo->detalles->isNotEmpty() && $pendientes === 0;
            $prestamo->esta_atrasado = !$prestamo->esta_devuelto && $prestamo->fecha_limite->lt($hoy);
            return $prestamo;
        });

        $estadisticas = [
            'libros' => Libro::count(),
            'ejemplares' => Ejemplar::count(),
            'personas' => Persona::count(),
            'usuarios' => Usuario::count(),
            'bibliotecarios' => Bibliotecario::count(),
            'prestamos' => Prestamo::count(),
            'devueltos' => $prestamosAnalisis->where('esta_devuelto', true)->count(),
            'activos' => $prestamosAnalisis->where('esta_devuelto', false)->where('esta_atrasado', false)->count(),
            'atrasados' => $prestamosAnalisis->where('esta_atrasado', true)->count(),
        ];

        $prestamosRecientes = Prestamo::with([
            'usuario.persona',
            'bibliotecario.persona',
            'detalles.ejemplar.libro',
            'detalles.devolucionesDetalle',
            'devolucion',
        ])->orderByDesc('id_prestamo')->limit(6)->get()->map(function (Prestamo $prestamo) use ($hoy): Prestamo {
            $pendientes = $prestamo->detalles->filter(fn ($detalle) => $detalle->devolucionesDetalle->isEmpty())->count();
            $prestamo->esta_devuelto = $prestamo->detalles->isNotEmpty() && $pendientes === 0;
            $prestamo->esta_atrasado = !$prestamo->esta_devuelto && $prestamo->fecha_limite->lt($hoy);
            return $prestamo;
        });

        return view('dashboard.index', compact('estadisticas', 'prestamosRecientes', 'hoy'));
    }
}
