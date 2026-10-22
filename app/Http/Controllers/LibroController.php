<?php

namespace App\Http\Controllers;

use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Editorial;
use App\Models\Libro;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class LibroController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));

        $libros = Libro::with(['autores.persona', 'categorias', 'editoriales'])
            ->withCount('ejemplares')
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($sub) use ($buscar): void {
                    $sub->where('titulo', 'like', "%{$buscar}%")
                        ->orWhere('isbn', 'like', "%{$buscar}%")
                        ->orWhereHas('autores.persona', function ($persona) use ($buscar): void {
                            $persona->where('nombre', 'like', "%{$buscar}%")
                                ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                                ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                        });
                });
            })
            ->orderBy('id_libro')
            ->paginate(12)
            ->withQueryString();

        return view('libros.index', compact('libros', 'buscar'));
    }

    public function create()
    {
        return view('libros.create', $this->catalogos());
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($data): void {
            $libro = Libro::create([
                'isbn' => $data['isbn'],
                'titulo' => $data['titulo'],
            ]);

            $libro->autores()->sync($data['autores']);
            $libro->categorias()->sync($data['categorias']);
            $libro->editoriales()->sync($data['editoriales']);
        });

        return redirect()->route('libros.index')->with('success', 'Libro registrado correctamente.');
    }

    public function edit(Libro $libro)
    {
        $libro->load(['autores.persona', 'categorias', 'editoriales']);

        return view('libros.edit', array_merge($this->catalogos(), compact('libro')));
    }

    public function update(Request $request, Libro $libro)
    {
        $data = $this->validar($request, $libro);

        DB::transaction(function () use ($data, $libro): void {
            $libro->update([
                'isbn' => $data['isbn'],
                'titulo' => $data['titulo'],
            ]);

            $libro->autores()->sync($data['autores']);
            $libro->categorias()->sync($data['categorias']);
            $libro->editoriales()->sync($data['editoriales']);
        });

        return redirect()->route('libros.index')->with('success', 'Libro actualizado correctamente.');
    }

    public function destroy(Libro $libro)
    {
        if ($libro->ejemplares()->exists()) {
            return back()->with('error', 'No se puede eliminar el libro porque tiene ejemplares registrados.');
        }

        try {
            $libro->delete();
        } catch (Throwable $e) {
            return back()->with('error', 'No fue posible eliminar el libro porque está relacionado con otros registros.');
        }

        return back()->with('success', 'Libro eliminado correctamente.');
    }

    private function catalogos(): array
    {
        return [
            'autores' => Autor::with('persona')->get()->sortBy(fn ($autor) => $autor->nombre_completo)->values(),
            'personasSinAutor' => Persona::whereDoesntHave('autor')->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre')->get(),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'editoriales' => Editorial::orderBy('nombre')->get(),
        ];
    }

    private function validar(Request $request, ?Libro $libro = null): array
    {
        return $request->validate([
            'isbn' => [
                'required',
                'string',
                'max:20',
                Rule::unique('libros', 'isbn')->ignore($libro?->id_libro, 'id_libro'),
            ],
            'titulo' => ['required', 'string', 'max:200'],
            'autores' => ['required', 'array', 'min:1'],
            'autores.*' => ['integer', 'exists:autores,id_autor'],
            'categorias' => ['required', 'array', 'min:1'],
            'categorias.*' => ['integer', 'exists:categorias,id_categoria'],
            'editoriales' => ['required', 'array', 'min:1'],
            'editoriales.*' => ['integer', 'exists:editoriales,id_editorial'],
        ], [
            'autores.required' => 'Selecciona al menos un autor.',
            'categorias.required' => 'Selecciona al menos una categoría.',
            'editoriales.required' => 'Selecciona al menos una editorial.',
        ]);
    }
}
