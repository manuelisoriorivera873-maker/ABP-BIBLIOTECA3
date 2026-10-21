<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Editorial;
use App\Models\EstadoEjemplar;
use App\Models\EstadoUsuario;
use App\Models\UbicacionLibro;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class CatalogoController extends Controller
{
    private array $config = [
        'categorias' => [
            'modelo' => Categoria::class,
            'pk' => 'id_categoria',
            'campo' => 'nombre',
            'titulo' => 'Categorías',
            'etiqueta' => 'Nombre de la categoría',
            'descripcion' => false,
            'max' => 100,
        ],
        'editoriales' => [
            'modelo' => Editorial::class,
            'pk' => 'id_editorial',
            'campo' => 'nombre',
            'titulo' => 'Editoriales',
            'etiqueta' => 'Nombre de la editorial',
            'descripcion' => false,
            'max' => 120,
        ],
        'ubicaciones' => [
            'modelo' => UbicacionLibro::class,
            'pk' => 'id_ubicacion',
            'campo' => 'nombre_ubicacion',
            'titulo' => 'Ubicaciones de libros',
            'etiqueta' => 'Nombre o código de ubicación',
            'descripcion' => true,
            'max' => 100,
        ],
        'estados-ejemplar' => [
            'modelo' => EstadoEjemplar::class,
            'pk' => 'id_estado_ejemplar',
            'campo' => 'nombre_estado',
            'titulo' => 'Estados de ejemplar',
            'etiqueta' => 'Estado del ejemplar',
            'descripcion' => false,
            'max' => 50,
        ],
        'estados-usuario' => [
            'modelo' => EstadoUsuario::class,
            'pk' => 'id_estado_usuario',
            'campo' => 'nombre_estado_usuario',
            'titulo' => 'Estados de usuario',
            'etiqueta' => 'Estado del usuario',
            'descripcion' => false,
            'max' => 50,
        ],
    ];

    public function index(Request $request, string $catalogo)
    {
        $c = $this->getConfig($catalogo);
        $modelo = $c['modelo'];
        $buscar = trim((string) $request->input('buscar'));
        $registros = $modelo::query()
            ->when($buscar !== '', fn ($q) => $q->where($c['campo'], 'like', "%{$buscar}%"))
            ->orderBy($c['campo'])
            ->paginate(15)
            ->withQueryString();

        return view('catalogos.index', compact('catalogo', 'c', 'registros', 'buscar'));
    }

    public function create(string $catalogo)
    {
        $c = $this->getConfig($catalogo);
        return view('catalogos.create', compact('catalogo', 'c'));
    }

    public function store(Request $request, string $catalogo)
    {
        $c = $this->getConfig($catalogo);
        $modelo = $c['modelo'];
        $modelo::create($this->validar($request, $c));

        return redirect()->route('catalogos.index', $catalogo)->with('success', 'Registro creado correctamente.');
    }

    public function edit(string $catalogo, int $id)
    {
        $c = $this->getConfig($catalogo);
        $registro = $c['modelo']::findOrFail($id);
        return view('catalogos.edit', compact('catalogo', 'c', 'registro'));
    }

    public function update(Request $request, string $catalogo, int $id)
    {
        $c = $this->getConfig($catalogo);
        $registro = $c['modelo']::findOrFail($id);
        $registro->update($this->validar($request, $c, $id));

        return redirect()->route('catalogos.index', $catalogo)->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(string $catalogo, int $id)
    {
        $c = $this->getConfig($catalogo);
        $registro = $c['modelo']::findOrFail($id);

        try {
            $registro->delete();
        } catch (Throwable $e) {
            return back()->with('error', 'No se puede eliminar porque el registro está siendo utilizado.');
        }

        return back()->with('success', 'Registro eliminado correctamente.');
    }

    public function rapido(Request $request, string $catalogo)
    {
        if (!in_array($catalogo, ['categorias', 'editoriales'], true)) {
            abort(404);
        }

        $c = $this->getConfig($catalogo);
        $modelo = $c['modelo'];
        $registro = $modelo::create($this->validar($request, $c));

        return response()->json([
            'id' => $registro->{$c['pk']},
            'texto' => $registro->{$c['campo']},
        ], 201);
    }

    private function getConfig(string $catalogo): array
    {
        if (!isset($this->config[$catalogo])) {
            abort(404);
        }

        return $this->config[$catalogo];
    }

    private function validar(Request $request, array $c, ?int $id = null): array
    {
        $rules = [
            $c['campo'] => [
                'required', 'string', 'max:' . $c['max'],
                Rule::unique((new ($c['modelo']))->getTable(), $c['campo'])->ignore($id, $c['pk']),
            ],
        ];
        if ($c['descripcion']) {
            $rules['descripcion'] = ['nullable', 'string', 'max:255'];
        }
        return $request->validate($rules);
    }
}
