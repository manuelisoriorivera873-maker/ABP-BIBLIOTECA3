<?php

use Database\Seeders\CompletarCatalogoSeeder;
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
if (! Schema::hasTable('autor_fichas') || ! Schema::hasTable('libro_fichas')) {
    throw new RuntimeException('Faltan las fichas bibliográficas previas. No se modifica el esquema original.');
}
echo 'Ampliación bibliográfica revisada: 27 títulos, sin inventario ni cuentas nuevas.'.PHP_EOL;
if (! in_array('--apply', $argv, true)) {
    exit;
}
$respaldo = (require __DIR__.'/respaldo-verificado.php')('ampliacion');
$historicas = [];
foreach (array_keys($respaldo['antes']) as $tabla) {
    $historicas[$tabla] = DB::table($tabla)->get()->map(fn ($fila) => (array) $fila)->all();
}
if (! Schema::hasColumn('libro_fichas', 'revision_bibliografica')) {
    if (Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_31_000006_add_revision_bibliografica.php', '--force' => true]) !== 0) {
        throw new RuntimeException(Artisan::output());
    }
}
if (! Schema::hasColumn('libro_fichas', 'idioma') || ! Schema::hasColumn('libro_fichas', 'portada_verificada_en')) {
    throw new RuntimeException('La migración bibliográfica está incompleta.');
}
(new CompletarCatalogoSeeder)->run();
foreach ($historicas as $tabla => $filas) {
    foreach ($filas as $fila) {
        if ($tabla === 'libro_fichas') {
            // Únicamente las sinopsis, referencias gráficas y campos de revisión
            // se enriquecen. UUID, ISBN verificado y demás identidad se conservan.
            $fila = array_diff_key($fila, array_flip(['sinopsis', 'portada_url', 'portada_fuente', 'revision_bibliografica', 'portada_verificada_en', 'idioma']));
        }
        if (! DB::table($tabla)->where($fila)->exists()) {
            throw new RuntimeException('Se alteró una fila protegida en '.$tabla);
        }
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
$primera = $instantanea();
(new CompletarCatalogoSeeder)->run();
if ($primera !== $instantanea()) {
    throw new RuntimeException('La carga no es idempotente.');
}
file_put_contents(storage_path('app/private/ampliacion/resultado.json'), json_encode(['respaldo' => $respaldo['respaldo'], 'antes' => $respaldo['antes'], 'despues' => $primera, 'historico_conservado' => true, 'idempotente' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Ampliación instalada. Filas protegidas intactas e idempotencia comprobada en todas las tablas.'.PHP_EOL;
