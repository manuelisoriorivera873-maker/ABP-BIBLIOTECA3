<?php

namespace App\Http\Controllers;

use App\Models\Ejemplar;
use App\Models\EstadoEjemplar;
use App\Models\Libro;
use App\Models\Prestamo;
use App\Models\SolicitudPrestamo;
use App\Models\Usuario;
use App\Services\SesionesOperativas;
use App\Services\SolicitarPrestamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudPrestamoController extends Controller
{
    public function index(Request $request, SesionesOperativas $sesiones)
    {
        $cuenta = $request->user();
        $disponible = false;
        if ($cuenta->rol === 'BIBLIOTECARIO' && $cuenta->id_bibliotecario && $cuenta->puede('solicitudes', 'editar')) {
            $disponible = $sesiones->actualizar($request);
            $sesiones->asignarPendientes();
        }
        $solicitudes = SolicitudPrestamo::with(['usuario.persona', 'detalles.libro', 'responsable'])
            ->when($cuenta->rol === 'USUARIO', fn ($q) => $q->where('id_usuario', $cuenta->id_usuario ?? 0))
            ->orderBy('id_solicitud')->paginate(12)->withQueryString();

        return view('solicitudes.index', compact('solicitudes', 'disponible'));
    }

    public function create(Request $request, SolicitarPrestamo $reglas)
    {
        $this->lector($request);
        $buscar = trim((string) $request->query('buscar', ''));
        $libros = Libro::query()->when($buscar !== '', fn ($q) => $q->where('titulo', 'like', '%'.$buscar.'%')->orWhere('isbn', 'like', '%'.$buscar.'%'))
            ->withCount(['ejemplares as disponibles' => fn ($q) => $q->whereIn('id_ejemplar', $reglas->disponibles()->select('id_ejemplar'))])
            ->orderBy('titulo')->get();

        return view('solicitudes.create', compact('libros', 'buscar'));
    }

    public function store(Request $request, SolicitarPrestamo $reglas, SesionesOperativas $sesiones)
    {
        $this->lector($request);
        $datos = $request->validate([
            'libros' => ['required', 'array', 'min:1', 'max:1000'],
            'libros.*' => ['nullable', 'integer', 'min:0', 'max:'.config('prestamos.maximo_por_titulo')],
        ]);
        $cantidades = array_filter(array_map('intval', $datos['libros']), fn ($v) => $v > 0);
        foreach (array_keys($cantidades) as $id) {
            abort_unless(ctype_digit((string) $id) && (string) (int) $id === (string) $id && Libro::whereKey($id)->exists(), 422, 'Título no válido.');
        }
        DB::transaction(function () use ($request, $reglas, $cantidades, $sesiones): void {
            $reglas->validar($request->user()->id_usuario, $cantidades);
            $solicitud = SolicitudPrestamo::create(['id_usuario' => $request->user()->id_usuario]);
            foreach ($cantidades as $libro => $cantidad) {
                $solicitud->detalles()->create(['id_libro' => $libro, 'cantidad' => $cantidad]);
            }
            $sesiones->asignarPendientes();
        }, 3);

        return redirect()->route('solicitudes.index')->with('success', 'Solicitud enviada. Si no hay bibliotecarios disponibles, permanecerá pendiente.');
    }

    public function disponibilidad(Request $request, SesionesOperativas $sesiones)
    {
        abort_unless($request->user()->rol === 'BIBLIOTECARIO' && $request->user()->id_bibliotecario, 403);
        $datos = $request->validate(['disponible' => ['required', 'boolean']]);
        $sesiones->actualizar($request, (bool) $datos['disponible']);

        // La sesión se guarda al terminar esta petición; el siguiente GET despacha la cola.
        return redirect()->route('solicitudes.index')->with('success', 'Disponibilidad actualizada.');
    }

    public function update(Request $request, int $solicitud, SolicitarPrestamo $reglas)
    {
        abort_unless(in_array($request->user()->rol, ['BIBLIOTECARIO', 'SUPERADMIN'], true), 403);
        $datos = $request->validate([
            'decision' => ['required', 'in:aprobar,rechazar,entregar'],
            'motivo' => ['required_if:decision,rechazar', 'nullable', 'string', 'max:500'],
            'fecha_limite' => ['required_if:decision,entregar', 'nullable', 'date', 'after_or_equal:today'],
        ]);
        DB::transaction(function () use ($request, $solicitud, $reglas, $datos): void {
            // Mismo orden de bloqueo que al enviar: lector, solicitud, ejemplares.
            $referencia = SolicitudPrestamo::findOrFail($solicitud);
            Usuario::whereKey($referencia->id_usuario)->lockForUpdate()->firstOrFail();
            $fila = SolicitudPrestamo::with('detalles')->lockForUpdate()->findOrFail($solicitud);
            abort_unless(in_array($fila->estado, ['PENDIENTE', 'APROBADA'], true), 409, 'La solicitud ya fue atendida.');
            abort_unless($request->user()->rol === 'SUPERADMIN' || ! $fila->asignada_a || $fila->asignada_a === $request->user()->getKey()
                || ! isset(app(SesionesOperativas::class)->disponibles()[$fila->asignada_a]), 403, 'La solicitud está asignada a otro bibliotecario disponible.');
            $estado = ['asignada_a' => $request->user()->getKey(), 'atendida_por' => $request->user()->getKey(), 'atendida_en' => now()];
            if ($datos['decision'] === 'rechazar') {
                $estado += ['estado' => 'RECHAZADA', 'motivo_rechazo' => $datos['motivo']];
            } else {
                $ids = $reglas->validar($fila->id_usuario, $fila->detalles->pluck('cantidad', 'id_libro')->all(), $fila->getKey());
                if ($datos['decision'] === 'aprobar') {
                    abort_unless($fila->estado === 'PENDIENTE', 409);
                    $estado += ['estado' => 'APROBADA'];
                } else {
                    abort_unless($fila->estado === 'APROBADA', 409, 'Primero debe aprobarse la solicitud.');
                    $personal = $request->user()->id_bibliotecario;
                    abort_unless($personal, 422, 'La entrega requiere una relación de bibliotecario en Personas.');
                    $prestado = EstadoEjemplar::where('nombre_estado', 'PRESTADO')->firstOrFail();
                    $prestamo = Prestamo::create([
                        'id_usuario' => $fila->id_usuario, 'id_bibliotecario' => $personal,
                        'fecha_prestamo' => today(), 'fecha_limite' => $datos['fecha_limite'],
                    ]);
                    foreach ($ids as $id) {
                        $prestamo->detalles()->create(['id_ejemplar' => $id]);
                    }
                    Ejemplar::whereKey($ids)->update(['id_estado_ejemplar' => $prestado->getKey()]);
                    $estado += ['estado' => 'ENTREGADA', 'id_prestamo' => $prestamo->getKey()];
                }
            }
            $fila->update($estado);
        }, 3);

        return redirect()->route('solicitudes.index')->with('success', 'Estado de la solicitud actualizado.');
    }

    private function lector(Request $request): void
    {
        abort_unless($request->user()->rol === 'USUARIO' && $request->user()->id_usuario, 403, 'Se requiere una cuenta de lector vinculada.');
    }
}
