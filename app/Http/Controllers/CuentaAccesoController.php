<?php

namespace App\Http\Controllers;

use App\Models\CuentaAcceso;
use App\Rules\LimiteBcrypt;
use App\Support\AdministrarCuenta;
use App\Support\PermisosBiblioteca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CuentaAccesoController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));
        $cuentas = CuentaAcceso::with(['lector.persona', 'empleado.persona'])
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($subquery) use ($buscar): void {
                    $subquery->where('nombre_usuario', 'like', "%{$buscar}%")
                        ->orWhereHas('lector.persona', fn ($persona) => $persona->where('nombre', 'like', "%{$buscar}%")->orWhere('apellido_paterno', 'like', "%{$buscar}%"))
                        ->orWhereHas('empleado.persona', fn ($persona) => $persona->where('nombre', 'like', "%{$buscar}%")->orWhere('apellido_paterno', 'like', "%{$buscar}%"));
                });
            })
            ->orderBy('id_cuenta')->paginate(15)->withQueryString();
        $totales = CuentaAcceso::selectRaw('rol, COUNT(*) as total')->groupBy('rol')->pluck('total', 'rol');
        $permisosRol = [];
        foreach (['BIBLIOTECARIO', 'USUARIO'] as $rol) {
            $permisosRol[$rol] = PermisosBiblioteca::vigentes($rol);
        }

        return view('cuentas.index', compact('cuentas', 'totales', 'permisosRol', 'buscar'));
    }

    public function create()
    {
        return redirect()->route('personas.index')->with('success', 'Para crear una cuenta y asignar su rol, abre Editar persona.');
    }

    public function store(Request $request)
    {
        abort(403, 'Las cuentas y sus roles se crean desde Personas → Editar persona.');
    }

    public function edit(CuentaAcceso $cuenta)
    {
        return view('cuentas.formulario', ['cuenta' => $cuenta]);
    }

    public function update(Request $request, CuentaAcceso $cuenta)
    {
        abort_if($request->hasAny(['rol', 'acceso', 'id_usuario', 'id_bibliotecario', 'permisos']), 403, 'Los roles se asignan exclusivamente desde Personas → Editar persona.');
        $data = $request->validate([
            'nombre_usuario' => ['required', 'regex:/^[a-zA-Z0-9._-]+$/', 'min:4', 'max:60', Rule::unique('cuentas_acceso', 'nombre_usuario')->ignore($cuenta->id_cuenta, 'id_cuenta')],
            'password' => ['nullable', 'string', 'min:12', new LimiteBcrypt, 'confirmed'],
            'activo' => ['required', 'boolean'],
        ]);
        app(AdministrarCuenta::class)->actualizar($cuenta, $data, $request->user());

        return redirect()->route('cuentas.index')->with('success', 'Cuenta actualizada.');
    }

    public function permisos(CuentaAcceso $cuenta)
    {
        abort_if($cuenta->rol === 'SUPERADMIN', 403);

        return redirect()->route('cuentas.roles.permisos', $cuenta->rol);
    }

    public function guardarPermisos(Request $request, CuentaAcceso $cuenta)
    {
        abort(403, 'Los permisos individuales son históricos. Configura la matriz del rol.');
    }

    public function permisosRol(string $rol)
    {
        abort_unless(in_array($rol, ['BIBLIOTECARIO', 'USUARIO'], true), 404);

        return view('cuentas.permisos', [
            'rol' => $rol, 'modulos' => PermisosBiblioteca::MODULOS,
            'acciones' => PermisosBiblioteca::ACCIONES, 'asignados' => PermisosBiblioteca::vigentes($rol),
        ]);
    }

    public function guardarPermisosRol(Request $request, string $rol)
    {
        abort_unless(in_array($rol, ['BIBLIOTECARIO', 'USUARIO'], true), 404);
        // Identificar la matriz de los errores de validación sin confiar en el campo enviado.
        $request->merge(['matriz_rol' => $rol]);
        $datos = $request->validate(['permisos' => ['sometimes', 'array'], 'permisos.*' => ['string', 'max:65']]);
        $nuevos = array_values(array_unique($datos['permisos'] ?? []));
        foreach ($nuevos as $valor) {
            $partes = explode(':', $valor);
            if (count($partes) !== 2 || ! PermisosBiblioteca::permiteRol($rol, $partes[0], $partes[1])) {
                throw ValidationException::withMessages(['permisos' => 'Uno de los permisos no es válido para este rol.']);
            }
            if ($partes[1] !== 'ver') {
                $nuevos[] = $partes[0].':ver';
            }
        }
        $nuevos = array_values(array_unique($nuevos));
        sort($nuevos);
        DB::transaction(function () use ($rol, $nuevos, $request): void {
            $cuentas = CuentaAcceso::orderBy('id_cuenta')->lockForUpdate()->get();
            $actor = $cuentas->firstWhere('id_cuenta', $request->user()->id_cuenta);
            abort_unless($actor && $actor->activo && $actor->rol === 'SUPERADMIN', 403);
            $anteriores = PermisosBiblioteca::vigentes($rol);
            DB::table('rol_permisos')->where('rol', $rol)->delete();
            foreach ($nuevos as $valor) {
                [$modulo, $accion] = explode(':', $valor, 2);
                DB::table('rol_permisos')->insert(compact('rol', 'modulo', 'accion'));
            }
            DB::table('historial_rol_permisos')->insert([
                'actor_id' => $actor->id_cuenta, 'rol' => $rol,
                'detalle' => json_encode(['evento' => 'CONFIGURAR_ROL', 'anteriores' => $anteriores, 'nuevos' => $nuevos], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('cuentas.roles.permisos', $rol)->with('success', 'Permisos del rol guardados. Todas sus cuentas heredan los cambios.');
    }

    public function historial()
    {
        $cambios = DB::table('historial_permisos as h')
            ->leftJoin('cuentas_acceso as actor', 'actor.id_cuenta', '=', 'h.actor_id')
            ->join('cuentas_acceso as destino', 'destino.id_cuenta', '=', 'h.cuenta_id')
            ->select('h.*', 'actor.nombre_usuario as actor', 'destino.nombre_usuario as destino')
            ->orderBy('h.id_cambio')->paginate(25, ['*'], 'cuentas_page')->withQueryString();

        $cambiosRoles = DB::table('historial_rol_permisos as h')
            ->leftJoin('cuentas_acceso as actor', 'actor.id_cuenta', '=', 'h.actor_id')
            ->select('h.*', 'actor.nombre_usuario as actor')->orderBy('h.id_cambio')
            ->paginate(25, ['*'], 'roles_page')->withQueryString();

        return view('cuentas.historial', compact('cambios', 'cambiosRoles'));
    }
}
