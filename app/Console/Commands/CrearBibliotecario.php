<?php

namespace App\Console\Commands;

use App\Support\CrearCuentaPorConsola;
use Illuminate\Console\Command;

class CrearBibliotecario extends Command
{
    protected $signature = 'biblioteca:crear-bibliotecario';
    protected $description = 'Crea una cuenta de acceso para un bibliotecario existente';

    public function handle(CrearCuentaPorConsola $creador): int
    {
        return $creador->ejecutar($this, 'BIBLIOTECARIO');
    }
}
