<?php

namespace App\Console\Commands;

use App\Models\CuentaAcceso;
use Illuminate\Console\Command;

class CrearSuperadmin extends Command
{
    protected $signature = 'biblioteca:crear-superadmin';
    protected $description = 'Crea la primera cuenta SUPERADMIN desde la consola local sin contraseña predefinida';

    public function handle(): int
    {
        if (CuentaAcceso::where('rol', 'SUPERADMIN')->exists()) {
            $this->error('Ya existe una cuenta SUPERADMIN; no se generó otra.');
            return self::FAILURE;
        }
        $nombre = trim((string) $this->ask('Nombre de acceso del SUPERADMIN'));
        if (!preg_match('/^[a-zA-Z0-9._-]{4,60}$/', $nombre)) {
            $this->error('Usa 4-60 caracteres: letras, números, punto, guion o guion bajo.');
            return self::FAILURE;
        }
        if (CuentaAcceso::where('nombre_usuario', $nombre)->exists()) {
            $this->error('Ese nombre de acceso ya existe.');
            return self::FAILURE;
        }
        $clave = (string) $this->secret('Contraseña nueva (mínimo 12 caracteres)');
        $confirmar = (string) $this->secret('Confirma la contraseña');
        if (strlen($clave) < 12 || $clave !== $confirmar) {
            $this->error('Las contraseñas no coinciden o son demasiado cortas.');
            return self::FAILURE;
        }
        CuentaAcceso::create([
            'nombre_usuario' => $nombre,
            'password' => $clave,
            'rol' => 'SUPERADMIN',
            'activo' => true,
        ]);
        $this->info('SUPERADMIN creado. Ingresa desde /login.');
        return self::SUCCESS;
    }
}
