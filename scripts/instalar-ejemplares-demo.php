<?php

use App\Models\Libro;
use Database\Seeders\EjemplaresDemostracionSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment(['local', 'development']) || DB::connection()->getDriverName() !== 'mysql'
    || DB::connection()->getDatabaseName() !== 'biblioteca2'
    || ! in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new RuntimeException('Este instalador solo admite biblioteca2 local de desarrollo.');
}
$datos = EjemplaresDemostracionSeeder::titulos();
$directorio = storage_path('app/private/ejemplares-demo/'.date('Ymd-His'));
if (! is_dir($directorio)) {
    mkdir($directorio, 0700, true);
}
$esquema = [];
foreach (['libros', 'ejemplares', 'estados_ejemplar', 'ubicaciones_libros', 'prestamos', 'detalle_prestamo'] as $tabla) {
    $esquema[$tabla] = DB::select('SHOW CREATE TABLE `'.$tabla.'`');
}
file_put_contents($directorio.'/esquema.json', json_encode($esquema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
$isbn = array_column($datos, 'isbn');
$libros = Libro::withCount('ejemplares')->whereIn('isbn', $isbn)->get();
echo json_encode(['entorno' => app()->environment(), 'titulos_objetivo' => count($datos), 'encontrados' => $libros->count(), 'sin_ejemplares' => $libros->where('ejemplares_count', 0)->count()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
if (! in_array('--apply', $argv, true) || ! in_array('--development', $argv, true)) {
    exit;
}
if ($libros->count() !== count($datos) || count(array_unique($isbn)) !== count($datos)) {
    throw new RuntimeException('El conjunto de títulos no coincide con los manifiestos revisados.');
}
$respaldo = (require __DIR__.'/respaldo-verificado.php')('ejemplares-demo');
$originales = [];
foreach (array_keys($respaldo['antes']) as $tabla) {
    $originales[$tabla] = DB::table($tabla)->get()->map(fn ($r) => (array) $r)->all();
}
DB::transaction(function () use ($originales): void {
    (new EjemplaresDemostracionSeeder)->run();
    foreach ($originales as $tabla => $filas) {
        foreach ($filas as $fila) {
            if (! DB::table($tabla)->where($fila)->exists()) {
                throw new RuntimeException('Se alteró un registro previo en '.$tabla);
            }
        }
        if (! in_array($tabla, ['ejemplares', 'ubicaciones_libros'], true) && DB::table($tabla)->count() !== count($filas)) {
            throw new RuntimeException('Cambió una tabla fuera del alcance: '.$tabla);
        }
    }
    $instantanea = function (): array {
        $estado = [];
        foreach (Schema::getTableListing() as $tabla) {
            $filas = DB::table($tabla)->get()->map(fn ($r) => json_encode((array) $r, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))->all();
            sort($filas);
            $estado[$tabla] = hash('sha256', implode("\n", $filas));
        }

        return $estado;
    };
    $primera = $instantanea();
    (new EjemplaresDemostracionSeeder)->run();
    if ($primera !== $instantanea()) {
        throw new RuntimeException('La segunda ejecución cambió datos.');
    }
});
$resultado = Libro::with(['ejemplares.estado', 'ejemplares.ubicacion'])->whereIn('isbn', $isbn)->orderBy('id_libro')->get()->map(fn ($l) => [
    'id_libro' => $l->getKey(), 'titulo' => $l->titulo, 'isbn' => $l->isbn,
    'ejemplares' => $l->ejemplares->map(fn ($e) => ['id_ejemplar' => $e->getKey(), 'codigo' => $e->codigo_inventario, 'estado' => $e->estado->nombre_estado, 'ubicacion' => $e->ubicacion->nombre_ubicacion])->all(),
])->all();
file_put_contents($directorio.'/resultado.json', json_encode(['respaldo' => $respaldo['respaldo'], 'antes' => $respaldo['antes'], 'idempotente' => true, 'registros_previos_intactos' => true, 'libros' => $resultado, 'total_libros' => Libro::count(), 'creados' => DB::table('ejemplares')->whereNotIn('id_ejemplar', array_column($originales['ejemplares'], 'id_ejemplar'))->get()->all()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Informe: '.$directorio.'/resultado.json'.PHP_EOL;
echo 'Ejemplares DEMO instalados; registros previos intactos y segunda ejecución sin cambios.'.PHP_EOL;
