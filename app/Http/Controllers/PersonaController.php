<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;
use Throwable;

class PersonaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));

        $personas = Persona::with(['usuario.estado', 'bibliotecario'])
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($subquery) use ($buscar): void {
                    $subquery
                        ->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('personas.index', compact('personas', 'buscar'));
    }

    public function create()
    {
        return view('personas.create');
    }

    public function store(Request $request)
    {
        Persona::create($this->validar($request));

        return redirect()
            ->route('personas.index')
            ->with('success', 'Persona registrada correctamente.');
    }

    public function show(Persona $persona)
    {
        $relaciones = [];
        if (auth()->user()->puede('usuarios', 'ver')) {
            $relaciones[] = 'usuario.estado';
        }
        if (auth()->user()->puede('bibliotecarios', 'ver')) {
            $relaciones[] = 'bibliotecario';
        }
        if (auth()->user()->puede('prestamos', 'ver')) {
            if (auth()->user()->puede('usuarios', 'ver')) {
                $relaciones[] = 'usuario.prestamos';
            }
            if (auth()->user()->puede('bibliotecarios', 'ver')) {
                $relaciones[] = 'bibliotecario.prestamos';
            }
        }
        $persona->load($relaciones);

        return view('personas.show', compact('persona'));
    }

    public function edit(Persona $persona)
    {
        return view('personas.edit', compact('persona'));
    }

    public function update(Request $request, Persona $persona)
    {
        $persona->update($this->validar($request));

        return redirect()
            ->route('personas.index')
            ->with('success', 'Persona actualizada correctamente.');
    }

    public function destroy(Persona $persona)
    {
        if ($persona->usuario()->exists() || $persona->bibliotecario()->exists()) {
            return back()->with(
                'error',
                'No se puede eliminar la persona porque está vinculada a un usuario o bibliotecario.'
            );
        }

        try {
            $persona->delete();
        } catch (Throwable $e) {
            return back()->with('error', 'No fue posible eliminar la persona.');
        }

        return back()->with('success', 'Persona eliminada correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'apellido_paterno' => ['required', 'string', 'max:80'],
            'apellido_materno' => ['required', 'string', 'max:80'],
        ]);
    }
}
