<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('biblioteca:info', function (): void {
    $this->info('Biblioteca 3 lista. Base de datos: biblioteca2.');
})->purpose('Muestra información básica del proyecto');
