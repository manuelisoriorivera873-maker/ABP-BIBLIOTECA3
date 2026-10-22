<?php

namespace App\Support;

use App\Models\Bibliotecario;
use App\Models\CuentaAcceso;
use App\Models\EstadoUsuario;
use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdministrarCuenta
{
    public function actualizar(CuentaAcceso $cuenta, array $datos, CuentaAcceso $actor, string $origen = 'cuentas'): void
    {
        abort_if($origen !== 'personas' && array_key_exists('rol', $datos), 403, 'Los roles se asignan exclusivamente desde Personas → Editar persona.');
        DB::transaction(function () use ($cuenta, $datos, $actor, $origen): void {
            $cuentas = $this->bloquearYAutorizar($actor);
            $cuenta = $cuentas->firstWhere('id_cuenta', $cuenta->id_cuenta);
            abort_unless($cuenta, 404);
            // Las credenciales no deben sobrescribir un rol cambiado desde Personas.
            $datos['rol'] ??= $cuenta->rol;
            if ($cuenta->rol === 'SUPERADMIN' && $cuenta->activo
                && ($datos['rol'] !== 'SUPERADMIN' || ! $datos['activo'])
                && $cuentas->where('rol', 'SUPERADMIN')->where('activo', true)->count() <= 1) {
                throw ValidationException::withMessages(['rol' => 'Debes conservar al menos un SUPERADMIN activo.']);
            }
            if (! $cuenta->activo && $datos['activo'] && ! in_array(password_get_info($cuenta->password)['algoName'], ['bcrypt', 'argon2i', 'argon2id'], true) && empty($datos['password'])) {
                throw ValidationException::withMessages(['activo' => 'La cuenta necesita una contraseña válida. Actualízala desde Cuentas y permisos antes de activarla.']);
            }
            $antes = $cuenta->only(['nombre_usuario', 'activo', 'rol']);
            if ($datos['rol'] !== $cuenta->rol) {
                $persona = $cuenta->lector?->persona ?? $cuenta->empleado?->persona;
                if ($datos['rol'] !== 'SUPERADMIN' && ! $persona) {
                    throw ValidationException::withMessages(['rol' => 'Esta cuenta no tiene persona vinculada. Requiere revisión administrativa.']);
                }
                if ($datos['rol'] !== 'SUPERADMIN') {
                    $this->vincular($cuenta, $persona, $datos);
                }
                $cuenta->rol = $datos['rol'];
            }
            $cuenta->nombre_usuario = $datos['nombre_usuario'];
            $cuenta->activo = (bool) $datos['activo'];
            if (filled($datos['password'] ?? null)) {
                $cuenta->password = $datos['password'];
            }
            $cuenta->save();
            $this->historial($cuenta, $actor, 'ACTUALIZAR_CUENTA', [
                'antes' => $antes,
                'despues' => $cuenta->only(['nombre_usuario', 'activo', 'rol']),
                'rol_anterior' => $antes['rol'],
                'rol_nuevo' => $cuenta->rol,
                'password_cambiada' => filled($datos['password'] ?? null),
                'origen' => $origen,
            ]);
        });
    }

    public function crearParaPersona(Persona $persona, array $datos, CuentaAcceso $actor): CuentaAcceso
    {
        return DB::transaction(function () use ($persona, $datos, $actor): CuentaAcceso {
            $this->bloquearYAutorizar($actor);
            $persona = Persona::whereKey($persona->id_persona)->lockForUpdate()->firstOrFail();
            if (CuentaAcceso::dePersona($persona->id_persona)->exists()) {
                throw ValidationException::withMessages(['acceso' => 'La persona ya tiene una cuenta. Abre de nuevo Editar persona para administrarla.']);
            }
            $cuenta = new CuentaAcceso([
                'nombre_usuario' => $datos['nombre_usuario'],
                'password' => $datos['password'],
                'rol' => $datos['rol'],
                'activo' => (bool) $datos['activo'],
            ]);
            // Un SUPERADMIN nuevo también necesita una relación para identificar su persona.
            $this->vincular($cuenta, $persona, $datos, $datos['rol'] === 'SUPERADMIN' ? 'BIBLIOTECARIO' : null);
            $cuenta->save();
            $this->historial($cuenta, $actor, 'CREAR_CUENTA', [
                'id_persona' => $persona->id_persona,
                'rol_anterior' => null,
                'rol_nuevo' => $cuenta->rol,
                'activo' => $cuenta->activo,
                'origen' => 'personas',
            ]);

            return $cuenta;
        });
    }

    private function bloquearYAutorizar(CuentaAcceso $actor)
    {
        // Mismo orden en ambos formularios para proteger al último administrador.
        $cuentas = CuentaAcceso::orderBy('id_cuenta')->lockForUpdate()->get();
        $administrador = $cuentas->firstWhere('id_cuenta', $actor->id_cuenta);
        abort_unless($administrador && $administrador->activo && $administrador->rol === 'SUPERADMIN', 403);

        return $cuentas;
    }

    private function vincular(CuentaAcceso $cuenta, Persona $persona, array $datos, ?string $rol = null): void
    {
        if (($rol ?? $datos['rol']) === 'BIBLIOTECARIO') {
            $empleado = Bibliotecario::firstOrCreate(['id_persona' => $persona->id_persona]);
            if (CuentaAcceso::where('id_bibliotecario', $empleado->id_bibliotecario)->when($cuenta->exists, fn ($query) => $query->whereKeyNot($cuenta->id_cuenta))->exists()) {
                throw ValidationException::withMessages(['rol' => 'La persona ya tiene otra cuenta de personal.']);
            }
            $cuenta->id_bibliotecario = $empleado->id_bibliotecario;
            $cuenta->id_usuario = null;
        } else {
            $lector = Usuario::where('id_persona', $persona->id_persona)->first();
            if (! $lector) {
                $estado = EstadoUsuario::whereRaw('UPPER(TRIM(nombre_estado_usuario)) = ?', ['ACTIVO'])->first();
                if (! $estado || empty($datos['telefono']) || Usuario::where('telefono', $datos['telefono'])->exists()) {
                    throw ValidationException::withMessages(['telefono' => 'Indica un teléfono disponible y verifica que exista el estado ACTIVO.']);
                }
                $lector = Usuario::create(['id_persona' => $persona->id_persona, 'telefono' => $datos['telefono'], 'id_estado_usuario' => $estado->id_estado_usuario]);
            }
            if (CuentaAcceso::where('id_usuario', $lector->id_usuario)->when($cuenta->exists, fn ($query) => $query->whereKeyNot($cuenta->id_cuenta))->exists()) {
                throw ValidationException::withMessages(['rol' => 'La persona ya tiene otra cuenta de lector.']);
            }
            $cuenta->id_usuario = $lector->id_usuario;
            $cuenta->id_bibliotecario = null;
        }
    }

    private function historial(CuentaAcceso $cuenta, CuentaAcceso $actor, string $evento, array $detalle): void
    {
        DB::table('historial_permisos')->insert([
            'actor_id' => $actor->id_cuenta,
            'cuenta_id' => $cuenta->id_cuenta,
            'evento' => $evento,
            'detalle' => json_encode($detalle, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }
}
