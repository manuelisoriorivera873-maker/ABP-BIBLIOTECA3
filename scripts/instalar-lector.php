<?php

use Database\Seeders\CatalogoVerificadoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (DB::connection()->getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'biblioteca2') {
    throw new RuntimeException('Conexión incorrecta.');
}
echo 'Instalación aditiva: fichas públicas y control de envíos; catálogo verificado sin inventario ficticio.'.PHP_EOL;
if (! in_array('--apply', $argv, true)) {
    exit;
}
if (Schema::hasTable('libro_fichas') !== Schema::hasTable('solicitud_envios')) {
    throw new RuntimeException('Esquema parcial. Inspecciona las tablas existentes; no se elimina ni reinicia nada.');
}
$respaldo = (require __DIR__.'/respaldo-verificado.php')('lector');
$historicas = [];
foreach (array_keys($respaldo['antes']) as $tabla) {
    $historicas[$tabla] = DB::table($tabla)->get()->map(fn ($fila) => (array) $fila)->all();
}
if (! Schema::hasTable('libro_fichas')) {
    if (Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_31_000004_create_libro_fichas.php', '--force' => true]) !== 0) {
        throw new RuntimeException(Artisan::output());
    }
}
if (Artisan::call('db:seed', ['--class' => CatalogoVerificadoSeeder::class, '--force' => true]) !== 0) {
    throw new RuntimeException(Artisan::output());
}
foreach ($historicas as $tabla => $filas) {
    foreach ($filas as $fila) {
        // Comparar cada fila previa íntegra; solo se permite añadir registros.
        if (! DB::table($tabla)->where($fila)->exists()) {
            throw new RuntimeException('Se alteró una fila histórica en '.$tabla);
        }
    }
}
echo 'Instalado. Todas las filas previas verificadas; sin modificaciones a préstamos, devoluciones ni ejemplares.'.PHP_EOL;
