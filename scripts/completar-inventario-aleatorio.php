<?php

use App\Models\Libro;
use App\Services\SolicitarPrestamo;
use Database\Seeders\EjemplaresDemostracionSeeder;
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
if (! app()->environment(['local', 'development']) || DB::connection()->getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'biblioteca2' || ! in_array(DB::connection()->getConfig('host'), ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('Solo biblioteca2 local de desarrollo.');
}
$datos = EjemplaresDemostracionSeeder::titulos();
$isbn = array_column($datos, 'isbn');
if (count(array_unique($isbn)) !== count($datos) || Libro::whereIn('isbn', $isbn)->count() !== count($datos)) {
    throw new RuntimeException('Manifiestos incompletos o ISBN repetidos.');
}
echo json_encode(['entorno' => app()->environment(), 'titulos_catalogo' => Libro::count(), 'titulos_demo' => count($datos), 'ejemplares_antes' => DB::table('ejemplares')->count(), 'objetivos_persistidos' => Schema::hasTable('inventario_demo_objetivos') ? DB::table('inventario_demo_objetivos')->count() : 0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
if (! in_array('--apply', $argv, true) || ! in_array('--development', $argv, true)) {
    exit;
}
$respaldo = (require __DIR__.'/respaldo-verificado.php')('inventario-aleatorio');
$originales = [];
foreach (array_keys($respaldo['antes']) as $tabla) {
    $originales[$tabla] = DB::table($tabla)->get()->map(fn ($f) => (array) $f)->all();
}
if (! Schema::hasTable('inventario_demo_objetivos')) {
    if (Artisan::call('migrate', ['--path' => 'database/migrations/2026_11_01_000008_create_inventario_demo_objetivos.php', '--force' => true]) !== 0) {
        throw new RuntimeException(Artisan::output());
    }
}
$instantanea = function (): array {
    $estado = [];
    foreach (Schema::getTableListing() as $tabla) {
        $filas = DB::table($tabla)->get()->map(fn ($f) => json_encode((array) $f, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))->all();
        sort($filas);
        $estado[$tabla] = hash('sha256', implode("\n", $filas));
    }
    ksort($estado);

    return $estado;
};
$resultado = DB::transaction(function () use ($originales, $instantanea): array {
    $seeder = new EjemplaresDemostracionSeeder;
    $resultado = $seeder->completarCantidadesAleatorias();
    foreach ($originales as $tabla => $filas) {
        foreach ($filas as $fila) {
            if (! DB::table($tabla)->where($fila)->exists()) {
                throw new RuntimeException('Se alteró una fila previa de '.$tabla);
            }
        }
        if (! in_array($tabla, ['ejemplares', 'migrations', 'inventario_demo_objetivos'], true) && DB::table($tabla)->count() !== count($filas)) {
            throw new RuntimeException('Cambió una tabla fuera de alcance: '.$tabla);
        }
    }
    $primera = $instantanea();
    $seeder->completarCantidadesAleatorias();
    if ($primera !== $instantanea()) {
        throw new RuntimeException('Falló la idempotencia.');
    }

    return $resultado;
});
foreach ($resultado as &$fila) {
    if (! isset($fila['excluido'])) {
        $fila['disponibles'] = app(SolicitarPrestamo::class)->disponibles()->where('id_libro', $fila['id_libro'])->count();
    }
}
unset($fila);
$directorio = storage_path('app/private/inventario-aleatorio/'.date('Ymd-His'));
if (! is_dir($directorio)) {
    mkdir($directorio, 0700, true);
}
$informe = ['respaldo' => $respaldo['respaldo'], 'antes' => $respaldo['antes'], 'ejemplares_antes' => count($originales['ejemplares']), 'creados' => array_sum(array_column($resultado, 'creados')), 'ejemplares_despues' => DB::table('ejemplares')->count(), 'titulos_procesados' => count(array_filter($resultado, fn ($r) => ! isset($r['excluido']))), 'registros_previos_intactos' => true, 'idempotente' => true, 'libros' => $resultado];
file_put_contents($directorio.'/resultado.json', json_encode($informe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode(array_diff_key($informe, array_flip(['antes', 'libros'])), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
echo 'Informe: '.$directorio.'/resultado.json'.PHP_EOL;
