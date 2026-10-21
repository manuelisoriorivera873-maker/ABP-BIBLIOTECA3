<?php

namespace App\Support;

use App\Models\Bibliotecario;
use App\Models\CuentaAcceso;
use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

final class CrearCuentaPorConsola
{
    public function ejecutar(Command $terminal, string $rol): int
    {
        if (!in_array($rol, ['BIBLIOTECARIO', 'USUARIO'], true)) {
            $terminal->error('Rol no permitido.');
            return Command::FAILURE;
        }

        $modelo = $rol === 'BIBLIOTECARIO' ? Bibliotecario::class : Usuario::class;
        $campo = $rol === 'BIBLIOTECARIO' ? 'id_bibliotecario' : 'id_usuario';
        $titulo = $rol === 'BIBLIOTECARIO' ? 'bibliotecarios' : 'usuarios lectores';

        $disponibles = $modelo::with('persona')
            ->whereDoesntHave('cuentaAcceso')
            ->get()
            ->sortBy(fn ($registro) => $registro->persona?->nombre_completo ?? '')
            ->values();

        if ($disponibles->isEmpty()) {
            $terminal->error("No hay {$titulo} disponibles sin cuenta. Registra uno primero desde el CRUD.");
            return Command::FAILURE;
        }

        $terminal->info("Registros de {$titulo} disponibles:");
        $terminal->table(
            ['ID', 'Persona'],
            $disponibles->map(fn ($registro) => [
                $registro->getAttribute($campo),
                $registro->persona?->nombre_completo ?? '(sin persona)',
            ])->all()
        );

        $id = trim((string) $terminal->ask("Escribe el ID del {$rol} al que asignarás la cuenta"));
        if (!ctype_digit($id) || !$disponibles->contains(fn ($registro) => (string) $registro->getAttribute($campo) === $id)) {
            $terminal->error('Debes seleccionar un ID válido de la tabla anterior.');
            return Command::FAILURE;
        }

        $nombre = trim((string) $terminal->ask('Nombre de acceso (4 a 60 caracteres)'));
        if (!preg_match('/^[a-zA-Z0-9._-]{4,60}$/', $nombre)) {
            $terminal->error('El nombre solo admite letras, números, punto, guion o guion bajo.');
            return Command::FAILURE;
        }
        if (CuentaAcceso::where('nombre_usuario', $nombre)->exists()) {
            $terminal->error('Ese nombre de acceso ya está registrado.');
            return Command::FAILURE;
        }

        $clave = (string) $terminal->secret('Contraseña (mínimo 12 caracteres)');
        $confirmacion = (string) $terminal->secret('Confirma la contraseña');
        if (strlen($clave) < 12 || $clave !== $confirmacion) {
            $terminal->error('La contraseña debe tener al menos 12 caracteres y coincidir con la confirmación.');
            return Command::FAILURE;
        }

        try {
            DB::transaction(function () use ($campo, $id, $nombre, $clave, $rol): void {
                $registro = ($rol === 'BIBLIOTECARIO' ? Bibliotecario::class : Usuario::class)::query()
                    ->whereKey((int) $id)->firstOrFail();

                if ($registro->cuentaAcceso()->exists()) {
                    throw new \RuntimeException('El registro seleccionado ya está asociado a una cuenta.');
                }

                $cuenta = CuentaAcceso::create([
                    'nombre_usuario' => $nombre,
                    'password' => Hash::make($clave),
                    'rol' => $rol,
                    'activo' => true,
                    'id_usuario' => $rol === 'USUARIO' ? (int) $id : null,
                    'id_bibliotecario' => $rol === 'BIBLIOTECARIO' ? (int) $id : null,
                ]);

                $permisos = collect(PermisosBiblioteca::iniciales($rol))
                    ->map(function ($permiso) use ($cuenta): array {
                        [$modulo, $accion] = explode(':', $permiso, 2);
                        return ['id_cuenta' => $cuenta->id_cuenta, 'modulo' => $modulo, 'accion' => $accion];
                    })->all();
                if ($permisos !== []) {
                    DB::table('cuenta_permisos')->insert($permisos);
                }

                DB::table('historial_permisos')->insert([
                    'actor_id' => null,
                    'cuenta_id' => $cuenta->id_cuenta,
                    'evento' => 'CREAR_CUENTA_CONSOLA',
                    'detalle' => json_encode(['rol' => $rol, 'vinculo' => $campo, 'id' => (int) $id], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                ]);
            });
        } catch (Throwable $error) {
            $terminal->error('No se pudo registrar la cuenta: ' . $error->getMessage());
            return Command::FAILURE;
        }

        $terminal->info("Cuenta {$rol} creada correctamente. Puedes iniciar sesión como {$nombre}.");
        $terminal->comment('Los permisos se pueden editar desde el panel SUPERADMIN.');
        return Command::SUCCESS;
    }
}
