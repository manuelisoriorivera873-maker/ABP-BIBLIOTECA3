<?php

namespace App\Console\Commands;

use App\Models\Bibliotecario;
use App\Models\CuentaAcceso;
use App\Models\Usuario;
use App\Support\PermisosBiblioteca;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CrearAccesoBiblioteca extends Command
{
    protected $signature = 'biblioteca:crear-acceso {rol : bibliotecario o usuario}';

    protected $description = 'Crear cuentas de bibliotecario o usuario';

    public function handle(): int
    {
        $rol = strtoupper(trim((string) $this->argument('rol')));

        if (!in_array($rol, ['BIBLIOTECARIO', 'USUARIO'], true)) {
            $this->error('El rol debe ser bibliotecario o usuario.');
            return self::FAILURE;
        }

        foreach (['cuentas_acceso', 'cuenta_permisos', 'historial_permisos'] as $tabla) {
            if (!Schema::hasTable($tabla)) {
                $this->error("Falta la tabla {$tabla}. Debe recuperarse primero.");
                return self::FAILURE;
            }
        }

        $modelo = $rol === 'BIBLIOTECARIO'
            ? Bibliotecario::class
            : Usuario::class;

        $campo = $rol === 'BIBLIOTECARIO'
            ? 'id_bibliotecario'
            : 'id_usuario';

        $tabla = $rol === 'BIBLIOTECARIO'
            ? 'bibliotecarios'
            : 'usuarios';

        if (!Schema::hasTable($tabla)) {
            $this->error("Falta la tabla {$tabla}.");
            return self::FAILURE;
        }

        $disponibles = $modelo::with('persona')
            ->whereDoesntHave('cuentaAcceso')
            ->get();

        if ($disponibles->isEmpty()) {
            $this->error("No hay {$tabla} disponibles sin cuenta.");
            return self::FAILURE;
        }

        $this->table(
            ['ID', 'Nombre'],
            $disponibles->map(fn ($r) => [
                $r->getAttribute($campo),
                $r->persona?->nombre_completo ?? 'Sin nombre'
            ])->all()
        );

        $id = trim((string) $this->ask('ID de la persona seleccionada'));

        if (
            !ctype_digit($id) ||
            !$disponibles->contains(
                fn ($r) => (string) $r->getAttribute($campo) === $id
            )
        ) {
            $this->error('Selecciona un ID válido.');
            return self::FAILURE;
        }

        $nombre = trim((string) $this->ask('Nombre de usuario'));

        if (
            !preg_match('/^[A-Za-z0-9._-]{4,60}$/', $nombre) ||
            CuentaAcceso::where('nombre_usuario', $nombre)->exists()
        ) {
            $this->error('Nombre inválido o ya registrado.');
            return self::FAILURE;
        }

        $clave = (string) $this->secret('Contraseña (mínimo 12 caracteres)');
        $confirmar = (string) $this->secret('Confirmar contraseña');

        if (strlen($clave) < 12 || $clave !== $confirmar) {
            $this->error('Las contraseñas no coinciden o son demasiado cortas.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use (
                $rol, $modelo, $campo, $id, $nombre, $clave
            ) {
                $registro = $modelo::findOrFail((int) $id);

                if ($registro->cuentaAcceso()->exists()) {
                    throw new \RuntimeException('La persona ya tiene cuenta.');
                }

                $cuenta = CuentaAcceso::create([
                    'nombre_usuario' => $nombre,
                    'password' => Hash::make($clave),
                    'rol' => $rol,
                    'activo' => true,
                    'id_usuario' => $rol === 'USUARIO' ? (int) $id : null,
                    'id_bibliotecario' => $rol === 'BIBLIOTECARIO' ? (int) $id : null
                ]);

                foreach (PermisosBiblioteca::iniciales($rol) as $permiso) {
                    [$modulo, $accion] = explode(':', $permiso, 2);

                    DB::table('cuenta_permisos')->insert([
                        'id_cuenta' => $cuenta->id_cuenta,
                        'modulo' => $modulo,
                        'accion' => $accion
                    ]);
                }

                DB::table('historial_permisos')->insert([
                    'actor_id' => null,
                    'cuenta_id' => $cuenta->id_cuenta,
                    'evento' => 'CREAR_CUENTA_CONSOLA',
                    'detalle' => json_encode([
                        'rol' => $rol,
                        'vinculo' => $campo,
                        'id' => (int) $id
                    ]),
                    'created_at' => now()
                ]);
            });
        } catch (Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Cuenta {$rol} creada correctamente: {$nombre}");

        return self::SUCCESS;
    }
}
