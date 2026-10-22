<?php

namespace App\Http\Controllers;

use App\Models\CuentaAcceso;
use App\Models\Persona;
use App\Rules\LimiteBcrypt;
use App\Support\AdministrarCuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class PersonaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));

        $relaciones = ['usuario.estado', 'bibliotecario'];
        if ($request->user()->rol === 'SUPERADMIN') {
            $relaciones = array_merge($relaciones, ['usuario.cuentaAcceso', 'bibliotecario.cuentaAcceso']);
        }
        $personas = Persona::with($relaciones)
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($subquery) use ($buscar): void {
                    $subquery
                        ->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('id_persona')
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
                $relaciones['usuario.prestamos'] = fn ($prestamos) => $prestamos->visiblesPara(auth()->user());
            }
            if (auth()->user()->puede('bibliotecarios', 'ver')) {
                $relaciones['bibliotecario.prestamos'] = fn ($prestamos) => $prestamos->visiblesPara(auth()->user());
            }
        }
        $persona->load($relaciones);

        return view('personas.show', compact('persona'));
    }

    public function edit(Request $request, Persona $persona)
    {
        $cuentasPersona = collect();
        $cuentaPersona = null;
        if ($request->user()->rol === 'SUPERADMIN') {
            $cuentasPersona = CuentaAcceso::dePersona($persona->id_persona)->orderBy('id_cuenta')->get();
            if ($request->filled('cuenta')) {
                $seleccion = $request->validate(['cuenta' => ['required', 'integer']]);
                $cuentaPersona = $cuentasPersona->firstWhere('id_cuenta', $seleccion['cuenta']);
                abort_unless($cuentaPersona, 404);
            } else {
                $cuentaPersona = $cuentasPersona->first();
            }
        }

        return view('personas.edit', compact('persona', 'cuentasPersona', 'cuentaPersona'));
    }

    public function update(Request $request, Persona $persona)
    {
        $esAdmin = $request->user()->rol === 'SUPERADMIN';
        abort_if(! $esAdmin && $request->hasAny(['acceso', 'rol', 'activo', 'cuenta_id', 'id_cuenta', 'id_usuario', 'id_bibliotecario', 'permisos', 'nombre_usuario', 'password', 'password_confirmation']), 403, 'Solo un SUPERADMIN puede modificar el acceso al sistema.');
        $datosPersona = $this->validar($request);
        $gestionar = $esAdmin && $request->boolean('acceso.gestionar');
        if ($esAdmin) {
            $request->validate(['acceso' => ['sometimes', 'array:gestionar,cuenta_id,rol,activo,telefono,nombre_usuario,password,password_confirmation'], 'acceso.gestionar' => ['sometimes', 'boolean']]);
        }
        DB::transaction(function () use ($request, $persona, $datosPersona, $gestionar): void {
            if ($gestionar) {
                CuentaAcceso::orderBy('id_cuenta')->lockForUpdate()->get();
            }
            $persona = Persona::whereKey($persona->id_persona)->lockForUpdate()->firstOrFail();
            if ($gestionar) {
                $request->validate(['acceso.cuenta_id' => ['nullable', 'integer']]);
                $cuenta = $request->filled('acceso.cuenta_id')
                    ? CuentaAcceso::dePersona($persona->id_persona)->whereKey($request->input('acceso.cuenta_id'))->firstOrFail()
                    : null;
                $datos = $request->validate([
                    'acceso.rol' => ['required', Rule::in(['USUARIO', 'BIBLIOTECARIO', 'SUPERADMIN'])],
                    'acceso.activo' => ['required', 'boolean'],
                    'acceso.telefono' => ['nullable', 'string', 'min:8', 'max:20', 'regex:/^[0-9 +()\-]+$/'],
                    'acceso.nombre_usuario' => [$cuenta ? 'prohibited' : 'required', 'string', 'min:4', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:cuentas_acceso,nombre_usuario'],
                    'acceso.password' => [$cuenta ? 'prohibited' : 'required', 'string', 'min:12', new LimiteBcrypt, 'confirmed'],
                ])['acceso'];
                $servicio = app(AdministrarCuenta::class);
                if ($cuenta) {
                    $datos['nombre_usuario'] = $cuenta->nombre_usuario;
                    $servicio->actualizar($cuenta, $datos, $request->user(), 'personas');
                } else {
                    $servicio->crearParaPersona($persona, $datos, $request->user());
                }
            }
            $persona->update($datosPersona);
        });

        return redirect()
            ->route('personas.edit', ['persona' => $persona, 'cuenta' => $request->input('acceso.cuenta_id')])
            ->with('success', $gestionar ? 'Persona, rol y acceso actualizados correctamente.' : 'Persona actualizada correctamente.');
    }

    public function destroy(Persona $persona)
    {
        if ($persona->usuario()->exists() || $persona->bibliotecario()->exists() || $persona->autor()->exists()) {
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
