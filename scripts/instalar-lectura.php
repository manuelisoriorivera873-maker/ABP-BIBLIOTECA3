<?php

use Database\Seeders\LecturaCatalogoSeeder;
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
$config = DB::connection()->getConfig();
if (! app()->environment(['local', 'development']) || DB::connection()->getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'biblioteca2' || ! in_array($config['host'], ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Solo se permite la base local de desarrollo biblioteca2.');
}
echo '32 ediciones españolas, favoritos y 5 recursos digitales. Sin ejemplares físicos nuevos.'.PHP_EOL;
if (! in_array('--apply', $argv, true)) {
    exit;
}
$respaldo = (require __DIR__.'/respaldo-verificado.php')('lectura-favoritos');
$historicas = [];
foreach (array_keys($respaldo['antes']) as $tabla) {
    $historicas[$tabla] = DB::table($tabla)->get()->map(fn ($f) => (array) $f)->all();
}
if (! Schema::hasTable('usuario_favoritos')) {
    if (Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_31_000007_create_lectura_y_favoritos.php', '--force' => true]) !== 0) {
        throw new RuntimeException(Artisan::output());
    }
}
$instantanea = function (): array {
    $estado = [];
    foreach (Schema::getTableListing() as $tabla) {
        $filas = DB::table($tabla)->get()->map(fn ($f) => json_encode((array) $f, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))->all();
        sort($filas);
        $estado[$tabla] = ['filas' => count($filas), 'sha256' => hash('sha256', implode("\n", $filas))];
    }
    ksort($estado);

    return $estado;
};
DB::transaction(function () use ($historicas, $instantanea): void {
    (new LecturaCatalogoSeeder)->run();
    foreach ($historicas as $tabla => $filas) {
        foreach ($filas as $fila) {
            // Se agrega únicamente la agrupación de obras; toda la identidad histórica se conserva.
            if ($tabla === 'libro_fichas') {
                unset($fila['obra_fuente']);
            }
            if (! DB::table($tabla)->where($fila)->exists()) {
                throw new RuntimeException('Fila histórica alterada: '.$tabla);
            }
        }
    }
    $primera = $instantanea();
    (new LecturaCatalogoSeeder)->run();
    if ($primera !== $instantanea()) {
        throw new RuntimeException('La instalación no es idempotente.');
    }
});
file_put_contents(storage_path('app/private/digital/resultado.json'), json_encode(['respaldo' => $respaldo['respaldo'], 'antes' => $respaldo['antes'], 'despues' => $instantanea(), 'historico_conservado' => true, 'idempotente' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Instalación completa; historial intacto e idempotencia comprobada.'.PHP_EOL;
