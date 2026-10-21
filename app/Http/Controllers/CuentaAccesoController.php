<?php

namespace App\Http\Controllers;

use App\Models\Bibliotecario;
use App\Models\CuentaAcceso;
use App\Models\Usuario;
use App\Support\PermisosBiblioteca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CuentaAccesoController extends Controller
{
    public function index()
    {
        $cuentas = CuentaAcceso::with(['lector.persona', 'empleado.persona', 'permisos'])
            ->orderByRaw("CASE WHEN rol = 'SUPERADMIN' THEN 0 WHEN rol = 'BIBLIOTECARIO' THEN 1 ELSE 2 END")
            ->orderBy('nombre_usuario')->get();
        return view('cuentas.index', ['cuentas' => $cuentas, 'modulos' => PermisosBiblioteca::MODULOS]);
    }

    public function create()
    {
        return view('cuentas.formulario', [
            'cuenta' => null,
            'lectores' => Usuario::with('persona')->whereDoesntHave('cuentaAcceso')->orderBy('id_usuario')->get(),
            'empleados' => Bibliotecario::with('persona')->whereDoesntHave('cuentaAcceso')->orderBy('id_bibliotecario')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre_usuario' => ['required', 'regex:/^[a-zA-Z0-9._-]+$/', 'min:4', 'max:60', 'unique:cuentas_acceso,nombre_usuario'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'rol' => ['required', Rule::in(['USUARIO', 'BIBLIOTECARIO'])],
            'id_usuario' => ['nullable', 'integer', 'exists:usuarios,id_usuario', 'unique:cuentas_acceso,id_usuario'],
            'id_bibliotecario' => ['nullable', 'integer', 'exists:bibliotecarios,id_bibliotecario', 'unique:cuentas_acceso,id_bibliotecario'],
        ]);
        $this->verificarVinculo($data);

        $cuenta = DB::transaction(function () use ($data, $request) {
            $cuenta = CuentaAcceso::create([
                'nombre_usuario' => $data['nombre_usuario'],
                'password' => $data['password'],
                'rol' => $data['rol'],
                'activo' => true,
                'id_usuario' => $data['rol'] === 'USUARIO' ? $data['id_usuario'] : null,
                'id_bibliotecario' => $data['rol'] === 'BIBLIOTECARIO' ? $data['id_bibliotecario'] : null,
            ]);
            $filas = [];
            foreach (PermisosBiblioteca::iniciales($cuenta->rol) as $permiso) {
                [$modulo, $accion] = explode(':', $permiso, 2);
                $filas[] = compact('modulo', 'accion') + ['id_cuenta' => $cuenta->id_cuenta];
            }
            DB::table('cuenta_permisos')->insert($filas);
            DB::table('historial_permisos')->insert([
                'actor_id' => $request->user()->id_cuenta,
                'cuenta_id' => $cuenta->id_cuenta,
                'evento' => 'CREAR_CUENTA',
                'detalle' => json_encode(['rol' => $cuenta->rol, 'permisos' => PermisosBiblioteca::iniciales($cuenta->rol)]),
                'created_at' => now(),
            ]);
            return $cuenta;
        });
        return redirect()->route('cuentas.permisos', $cuenta)->with('success', 'Cuenta creada; ahora puedes personalizar sus permisos.');
    }

    public function edit(CuentaAcceso $cuenta)
    {
        abort_if($cuenta->rol === 'SUPERADMIN', 403);
        return view('cuentas.formulario', ['cuenta' => $cuenta]);
    }

    public function update(Request $request, CuentaAcceso $cuenta)
    {
        abort_if($cuenta->rol === 'SUPERADMIN', 403);
        $data = $request->validate([
            'nombre_usuario' => ['required', 'regex:/^[a-zA-Z0-9._-]+$/', 'min:4', 'max:60', Rule::unique('cuentas_acceso', 'nombre_usuario')->ignore($cuenta->id_cuenta, 'id_cuenta')],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'activo' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($cuenta, $data, $request): void {
            $anterior = ['nombre_usuario' => $cuenta->nombre_usuario, 'activo' => $cuenta->activo];
            $cuenta->nombre_usuario = $data['nombre_usuario'];
            $cuenta->activo = (bool) $data['activo'];
            if (filled($data['password'] ?? null)) {
                $cuenta->password = $data['password'];
            }
            $cuenta->save();
            DB::table('historial_permisos')->insert([
                'actor_id' => $request->user()->id_cuenta,
                'cuenta_id' => $cuenta->id_cuenta,
                'evento' => 'ACTUALIZAR_CUENTA',
                'detalle' => json_encode(['antes' => $anterior, 'despues' => ['nombre_usuario' => $cuenta->nombre_usuario, 'activo' => $cuenta->activo], 'password_cambiada' => filled($data['password'] ?? null)]),
                'created_at' => now(),
            ]);
        });
        return redirect()->route('cuentas.index')->with('success', 'Cuenta actualizada.');
    }

    public function permisos(CuentaAcceso $cuenta)
    {
        abort_if($cuenta->rol === 'SUPERADMIN', 403, 'Los permisos del SUPERADMIN no se modifican desde este panel.');
        $asignados = $cuenta->permisos->map(fn ($permiso) => $permiso->modulo . ':' . $permiso->accion)->all();
        return view('cuentas.permisos', [
            'cuenta' => $cuenta,
            'modulos' => PermisosBiblioteca::MODULOS,
            'acciones' => PermisosBiblioteca::ACCIONES,
            'asignados' => $asignados,
        ]);
    }

    public function guardarPermisos(Request $request, CuentaAcceso $cuenta)
    {
        abort_if($cuenta->rol === 'SUPERADMIN', 403);
        $datos = $request->validate([
            'permisos' => ['sometimes', 'array'],
            'permisos.*' => ['string', 'max:65'],
        ]);
        $nuevos = array_values(array_unique($datos['permisos'] ?? []));
        foreach ($nuevos as $valor) {
            $partes = explode(':', $valor);
            if (count($partes) !== 2 || !PermisosBiblioteca::permiteRol($cuenta->rol, $partes[0], $partes[1])) {
                throw ValidationException::withMessages(['permisos' => 'Uno de los permisos enviados no es válido para este rol.']);
            }
        }
        sort($nuevos);
        DB::transaction(function () use ($nuevos, $cuenta, $request): void {
            $actuales = $cuenta->permisos()->get()->map(fn ($p) => $p->modulo . ':' . $p->accion)->all();
            sort($actuales);
            $cuenta->permisos()->delete();
            foreach ($nuevos as $valor) {
                [$modulo, $accion] = explode(':', $valor, 2);
                $cuenta->permisos()->create(compact('modulo', 'accion'));
            }
            DB::table('historial_permisos')->insert([
                'actor_id' => $request->user()->id_cuenta,
                'cuenta_id' => $cuenta->id_cuenta,
                'evento' => 'CAMBIAR_PERMISOS',
                'detalle' => json_encode(['anteriores' => $actuales, 'nuevos' => $nuevos], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        });
        return redirect()->route('cuentas.permisos', $cuenta)->with('success', 'Permisos guardados y aplicados.');
    }

    public function historial()
    {
        $cambios = DB::table('historial_permisos as h')
            ->leftJoin('cuentas_acceso as actor', 'actor.id_cuenta', '=', 'h.actor_id')
            ->join('cuentas_acceso as destino', 'destino.id_cuenta', '=', 'h.cuenta_id')
            ->select('h.*', 'actor.nombre_usuario as actor', 'destino.nombre_usuario as destino')
            ->orderByDesc('h.id_cambio')->paginate(25);
        return view('cuentas.historial', compact('cambios'));
    }

    private function verificarVinculo(array $data): void
    {
        if ($data['rol'] === 'USUARIO' && (empty($data['id_usuario']) || !empty($data['id_bibliotecario']))) {
            throw ValidationException::withMessages(['id_usuario' => 'Vincula exactamente un lector existente.']);
        }
        if ($data['rol'] === 'BIBLIOTECARIO' && (empty($data['id_bibliotecario']) || !empty($data['id_usuario']))) {
            throw ValidationException::withMessages(['id_bibliotecario' => 'Vincula exactamente un bibliotecario existente.']);
        }
    }
}
