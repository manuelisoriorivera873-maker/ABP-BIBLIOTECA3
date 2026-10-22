<?php

namespace App\Http\Controllers;

use App\Models\Ejemplar;
use App\Models\EstadoEjemplar;
use App\Models\Libro;
use App\Models\UbicacionLibro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class EjemplarController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));
        $estado = $request->input('estado');

        $ejemplares = Ejemplar::with(['libro', 'ubicacion', 'estado'])
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($subquery) use ($buscar): void {
                    $subquery
                        ->where('codigo_inventario', 'like', "%{$buscar}%")
                        ->orWhereHas('libro', function ($libro) use ($buscar): void {
                            $libro
                                ->where('titulo', 'like', "%{$buscar}%")
                                ->orWhere('isbn', 'like', "%{$buscar}%");
                        });
                });
            })
            ->when($estado, fn ($query) => $query->where('id_estado_ejemplar', $estado))
            ->orderBy('id_ejemplar')
            ->paginate(15)
            ->withQueryString();

        $estados = EstadoEjemplar::orderBy('nombre_estado')->get();

        return view('ejemplares.index', compact('ejemplares', 'estados', 'buscar', 'estado'));
    }

    public function create()
    {
        return view('ejemplares.create', $this->catalogos());
    }

    public function store(Request $request)
    {
        $data = $this->validar($request, null, true);
        $cantidad = (int) ($data['cantidad'] ?? 1);

        if ($cantidad > 1 && ! empty($data['codigo_inventario'])) {
            throw ValidationException::withMessages([
                'codigo_inventario' => 'Para registrar varios ejemplares deja vacío el código de inventario y el sistema los generará automáticamente.',
            ]);
        }

        DB::transaction(function () use ($data, $cantidad): void {
            for ($i = 0; $i < $cantidad; $i++) {
                Ejemplar::create([
                    'codigo_inventario' => $data['codigo_inventario'] ?: $this->siguienteCodigo(),
                    'id_libro' => $data['id_libro'],
                    'id_ubicacion' => $data['id_ubicacion'],
                    'id_estado_ejemplar' => $data['id_estado_ejemplar'],
                ]);
            }
        });

        $mensaje = $cantidad === 1
            ? 'Ejemplar registrado correctamente.'
            : "{$cantidad} ejemplares registrados correctamente.";

        return redirect()->route('ejemplares.index')->with('success', $mensaje);
    }

    public function edit(Ejemplar $ejemplar)
    {
        return view('ejemplares.edit', array_merge($this->catalogos(), compact('ejemplar')));
    }

    public function update(Request $request, Ejemplar $ejemplar)
    {
        $data = $this->validar($request, $ejemplar);
        $data['codigo_inventario'] = $data['codigo_inventario'] ?: $ejemplar->codigo_inventario;
        $ejemplar->update($data);

        return redirect()->route('ejemplares.index')->with('success', 'Ejemplar actualizado correctamente.');
    }

    public function destroy(Ejemplar $ejemplar)
    {
        if ($ejemplar->detallesPrestamo()->exists()) {
            return back()->with('error', 'No se puede eliminar el ejemplar porque aparece en el historial de préstamos.');
        }

        try {
            $ejemplar->delete();
        } catch (Throwable $e) {
            return back()->with('error', 'No fue posible eliminar el ejemplar.');
        }

        return back()->with('success', 'Ejemplar eliminado correctamente.');
    }

    private function catalogos(): array
    {
        return [
            'libros' => Libro::orderBy('titulo')->get(),
            'ubicaciones' => UbicacionLibro::orderBy('nombre_ubicacion')->get(),
            'estados' => EstadoEjemplar::orderBy('nombre_estado')->get(),
            'estadoPredeterminado' => EstadoEjemplar::where('nombre_estado', 'DISPONIBLE')
                ->value('id_estado_ejemplar'),
        ];
    }

    private function validar(
        Request $request,
        ?Ejemplar $ejemplar = null,
        bool $incluyeCantidad = false
    ): array {
        $reglas = [
            'codigo_inventario' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('ejemplares', 'codigo_inventario')->ignore($ejemplar?->id_ejemplar, 'id_ejemplar'),
            ],
            'id_libro' => ['required', 'integer', 'exists:libros,id_libro'],
            'id_ubicacion' => ['required', 'integer', 'exists:ubicaciones_libros,id_ubicacion'],
            'id_estado_ejemplar' => ['required', 'integer', 'exists:estados_ejemplar,id_estado_ejemplar'],
        ];

        if ($incluyeCantidad) {
            $reglas['cantidad'] = ['nullable', 'integer', 'min:1', 'max:20'];
        }

        return $request->validate($reglas, [
            'cantidad.max' => 'Puedes registrar como máximo 20 ejemplares a la vez.',
        ]);
    }

    private function siguienteCodigo(): string
    {
        $ultimo = (int) (Ejemplar::max('id_ejemplar') ?? 0) + 1;
        $codigo = 'EJ-'.str_pad((string) $ultimo, 5, '0', STR_PAD_LEFT);

        while (Ejemplar::where('codigo_inventario', $codigo)->exists()) {
            $ultimo++;
            $codigo = 'EJ-'.str_pad((string) $ultimo, 5, '0', STR_PAD_LEFT);
        }

        return $codigo;
    }
}
