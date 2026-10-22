<?php

namespace App\Http\Controllers;

use App\Models\Bibliotecario;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class BibliotecarioController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));

        $bibliotecarios = Bibliotecario::with('persona')
            ->withCount(['prestamos' => fn ($prestamos) => $prestamos->visiblesPara($request->user())])
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->whereHas('persona', function ($persona) use ($buscar): void {
                    $persona->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('id_bibliotecario')
            ->paginate(15)
            ->withQueryString();

        return view('bibliotecarios.index', compact('bibliotecarios', 'buscar'));
    }

    public function create()
    {
        return view('bibliotecarios.create', ['personas' => Persona::whereDoesntHave('bibliotecario')->orderBy('nombre')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['id_persona' => ['required', 'integer', 'exists:personas,id_persona', 'unique:bibliotecarios,id_persona']]);
        Bibliotecario::create($data);

        return redirect()->route('bibliotecarios.index')->with('success', 'Bibliotecario registrado correctamente.');
    }

    public function edit(Bibliotecario $bibliotecario)
    {
        $bibliotecario->load('persona');

        return view('bibliotecarios.edit', compact('bibliotecario'));
    }

    public function update(Request $request, Bibliotecario $bibliotecario)
    {
        $bibliotecario->persona->update($this->validar($request));

        return redirect()->route('bibliotecarios.index')->with('success', 'Bibliotecario actualizado correctamente.');
    }

    public function destroy(Bibliotecario $bibliotecario)
    {
        if ($bibliotecario->cuentaAcceso()->exists() || $bibliotecario->prestamos()->exists()) {
            return back()->with('error', 'No se puede eliminar el bibliotecario porque tiene una cuenta o préstamos registrados.');
        }

        try {
            DB::transaction(function () use ($bibliotecario): void {
                $persona = $bibliotecario->persona;
                $bibliotecario->delete();

                // La persona puede tener otras funciones; se conserva su registro.
            });
        } catch (Throwable $e) {
            return back()->with('error', 'No fue posible eliminar el bibliotecario.');
        }

        return back()->with('success', 'Bibliotecario eliminado correctamente.');
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
