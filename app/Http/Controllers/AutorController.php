<?php

namespace App\Http\Controllers;

use App\Models\Autor;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class AutorController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));

        $autores = Autor::with('persona')
            ->withCount('libros')
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->whereHas('persona', function ($persona) use ($buscar): void {
                    $persona
                        ->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                });
            })
            ->orderBy(
                Persona::select('apellido_paterno')
                    ->whereColumn('personas.id_persona', 'autores.id_persona')
                    ->limit(1)
            )
            ->paginate(15)
            ->withQueryString();

        return view('autores.index', compact('autores', 'buscar'));
    }

    public function create()
    {
        return view('autores.create', [
            'personasDisponibles' => $this->personasDisponibles(),
        ]);
    }

    public function store(Request $request)
    {
        Autor::create($this->validar($request));

        return redirect()->route('autores.index')->with('success', 'Autor registrado correctamente y vinculado a una persona existente.');
    }

    public function edit(Autor $autor)
    {
        $autor->load('persona');

        return view('autores.edit', [
            'autor' => $autor,
            'personasDisponibles' => $this->personasDisponibles($autor->id_persona),
        ]);
    }

    public function update(Request $request, Autor $autor)
    {
        $autor->update($this->validar($request, $autor));

        return redirect()->route('autores.index')->with('success', 'Autor actualizado correctamente.');
    }

    public function destroy(Autor $autor)
    {
        if ($autor->libros()->exists()) {
            return back()->with('error', 'No se puede eliminar el autor porque está asignado a uno o más libros.');
        }

        try {
            $autor->delete();
        } catch (Throwable $e) {
            return back()->with('error', 'No fue posible eliminar el autor.');
        }

        return back()->with('success', 'Autor eliminado correctamente.');
    }

    public function rapido(Request $request)
    {
        $autor = Autor::with('persona')->create($this->validar($request));
        $autor->load('persona');

        return response()->json([
            'id' => $autor->id_autor,
            'texto' => $autor->nombre_completo,
        ], 201);
    }

    private function validar(Request $request, ?Autor $autor = null): array
    {
        return $request->validate([
            'id_persona' => [
                'required',
                'integer',
                'exists:personas,id_persona',
                Rule::unique('autores', 'id_persona')->ignore($autor?->id_autor, 'id_autor'),
            ],
        ], [
            'id_persona.required' => 'Selecciona una persona para registrarla como autor.',
            'id_persona.unique' => 'La persona seleccionada ya está registrada como autor.',
        ]);
    }

    private function personasDisponibles(?int $permitirActual = null)
    {
        return Persona::query()
            ->where(function ($query) use ($permitirActual): void {
                $query->whereDoesntHave('autor');

                if ($permitirActual) {
                    $query->orWhere('id_persona', $permitirActual);
                }
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }
}
