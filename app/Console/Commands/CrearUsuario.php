<?php

namespace App\Console\Commands;

use App\Support\CrearCuentaPorConsola;
use Illuminate\Console\Command;

class CrearUsuario extends Command
{
    protected $signature = 'biblioteca:crear-usuario';
    protected $description = 'Crea una cuenta de acceso para un usuario lector existente';

    public function handle(CrearCuentaPorConsola $creador): int
    {
        return $creador->ejecutar($this, 'USUARIO');
    }
}
