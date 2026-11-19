<?php

use Database\Seeders\EnriquecerCatalogoSeeder;
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
echo 'Enriquecimiento aditivo: 15 títulos y 12 biografías; sin ejemplares ficticios.'.PHP_EOL;
if (! in_array('--apply', $argv, true)) {
    exit;
}
if (! Schema::hasTable('libro_fichas')) {
    throw new RuntimeException('Esquema parcial. Inspecciona las tablas existentes; no se elimina ni reinicia nada.');
}
$respaldo = (require __DIR__.'/respaldo-verificado.php')('enriquecimiento');
$historicas = [];
foreach (array_keys($respaldo['antes']) as $tabla) {
    $historicas[$tabla] = DB::table($tabla)->get()->map(fn ($fila) => (array) $fila)->all();
}
if (! Schema::hasTable('autor_fichas')) {
    if (Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_31_000005_create_fichas_bibliograficas.php', '--force' => true]) !== 0) {
        throw new RuntimeException(Artisan::output());
    }
}
if (Artisan::call('db:seed', ['--class' => EnriquecerCatalogoSeeder::class, '--force' => true]) !== 0) {
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
// Incluir también las tablas nuevas en la comprobación de idempotencia.
$instantaneaCompleta = function (): array {
    $estado = [];
    foreach (Schema::getTableListing() as $tabla) {
        $filas = DB::table($tabla)->get()->map(fn ($f) => json_encode((array) $f, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))->all();
        sort($filas);
        $estado[$tabla] = ['filas' => count($filas), 'sha256' => hash('sha256', implode("\n", $filas))];
    }
    ksort($estado);

    return $estado;
};
$primera = $instantaneaCompleta();
(new EnriquecerCatalogoSeeder)->run();
if ($primera !== $instantaneaCompleta()) {
    throw new RuntimeException('El procedimiento no fue idempotente.');
}
file_put_contents(storage_path('app/private/enriquecimiento/resultado.json'), json_encode(['respaldo' => $respaldo['respaldo'], 'antes' => $respaldo['antes'], 'despues' => $primera, 'historico_conservado' => true, 'idempotente' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Instalado. Todas las filas previas verificadas; sin modificaciones a préstamos, devoluciones ni ejemplares.'.PHP_EOL;
