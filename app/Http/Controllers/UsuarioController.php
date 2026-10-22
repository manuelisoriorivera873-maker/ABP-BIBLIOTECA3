<?php

namespace App\Http\Controllers;

use App\Models\EstadoUsuario;
use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));

        $usuarios = Usuario::with(['persona', 'estado'])
            ->withCount(['prestamos' => fn ($prestamos) => $prestamos->visiblesPara($request->user())])
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($subquery) use ($buscar): void {
                    $subquery
                        ->where('telefono', 'like', "%{$buscar}%")
                        ->orWhereHas('persona', function ($persona) use ($buscar): void {
                            $persona
                                ->where('nombre', 'like', "%{$buscar}%")
                                ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                                ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                        });
                });
            })
            ->orderBy('id_usuario')
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', compact('usuarios', 'buscar'));
    }

    public function create()
    {
        $estados = EstadoUsuario::orderBy('nombre_estado_usuario')->get();
        $estadoPredeterminado = EstadoUsuario::where('nombre_estado_usuario', 'ACTIVO')
            ->value('id_estado_usuario');

        $personasDisponibles = Persona::whereDoesntHave('usuario')->orderBy('nombre')->get();

        return view('usuarios.create', compact('estados', 'estadoPredeterminado', 'personasDisponibles'));
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($data): void {
            $persona = ! empty($data['id_persona'])
                ? Persona::whereKey($data['id_persona'])->lockForUpdate()->firstOrFail()
                : null;
            if ($persona && $persona->usuario()->exists()) {
                throw ValidationException::withMessages(['id_persona' => 'La persona ya está registrada como lector.']);
            }
            $persona ??= Persona::create([
                'nombre' => $data['nombre'],
                'apellido_paterno' => $data['apellido_paterno'],
                'apellido_materno' => $data['apellido_materno'],
            ]);

            Usuario::create([
                'telefono' => $data['telefono'],
                'id_persona' => $persona->id_persona,
                'id_estado_usuario' => $data['id_estado_usuario'],
            ]);
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario registrado correctamente.');
    }

    public function edit(Usuario $usuario)
    {
        $usuario->load('persona');
        $estados = EstadoUsuario::orderBy('nombre_estado_usuario')->get();

        $estadoPredeterminado = null;

        return view('usuarios.edit', compact('usuario', 'estados', 'estadoPredeterminado'));
    }

    public function update(Request $request, Usuario $usuario)
    {
        $data = $this->validar($request, $usuario);

        DB::transaction(function () use ($data, $usuario): void {
            $usuario->persona->update([
                'nombre' => $data['nombre'],
                'apellido_paterno' => $data['apellido_paterno'],
                'apellido_materno' => $data['apellido_materno'],
            ]);

            $usuario->update([
                'telefono' => $data['telefono'],
                'id_estado_usuario' => $data['id_estado_usuario'],
            ]);
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Usuario $usuario)
    {
        if ($usuario->cuentaAcceso()->exists() || $usuario->prestamos()->exists()) {
            return back()->with('error', 'No se puede eliminar el usuario porque tiene una cuenta o préstamos registrados.');
        }

        try {
            DB::transaction(function () use ($usuario): void {
                $persona = $usuario->persona;
                $usuario->delete();

                // Conservar la persona y sus demás funciones e historial.
            });
        } catch (Throwable $e) {
            return back()->with('error', 'No fue posible eliminar el usuario.');
        }

        return back()->with('success', 'Usuario eliminado correctamente.');
    }

    private function validar(Request $request, ?Usuario $usuario = null): array
    {
        $personaExistente = ! $usuario && $request->filled('id_persona');

        return $request->validate([
            'id_persona' => [$usuario ? 'prohibited' : 'nullable', 'integer', 'exists:personas,id_persona', 'unique:usuarios,id_persona'],
            'nombre' => [Rule::excludeIf($personaExistente), 'required', 'string', 'max:80'],
            'apellido_paterno' => [Rule::excludeIf($personaExistente), 'required', 'string', 'max:80'],
            'apellido_materno' => [Rule::excludeIf($personaExistente), 'required', 'string', 'max:80'],
            'telefono' => [
                'required',
                'string',
                'min:8',
                'max:20',
                'regex:/^[0-9 +()\-]+$/',
                Rule::unique('usuarios', 'telefono')->ignore($usuario?->id_usuario, 'id_usuario'),
            ],
            'id_estado_usuario' => ['required', 'integer', 'exists:estados_usuario,id_estado_usuario'],
        ]);
    }
}
