<?php

use App\Models\Libro;
use App\Services\SolicitarPrestamo;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment(['local', 'development']) || DB::connection()->getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'biblioteca2') {
    throw new RuntimeException('Solo verificación de biblioteca2 local.');
}
$filas = [];
$errores = [];
foreach (DB::table('inventario_demo_objetivos')->orderBy('id_libro')->get() as $objetivo) {
    $l = Libro::findOrFail($objetivo->id_libro);
    $total = $l->ejemplares()->count();
    if ($objetivo->objetivo < 1 || $objetivo->objetivo > 20 || $total < max($objetivo->objetivo, $objetivo->existentes_al_asignar) || $objetivo->isbn !== $l->isbn) {
        $errores[] = $l->titulo;
    }
    $filas[] = ['id_libro' => $l->getKey(), 'titulo' => $l->titulo, 'objetivo' => $objetivo->objetivo, 'total' => $total, 'disponibles' => app(SolicitarPrestamo::class)->disponibles()->where('id_libro', $l->getKey())->count()];
}
$duplicados = DB::table('ejemplares')->select('codigo_inventario')->groupBy('codigo_inventario')->havingRaw('COUNT(*) > 1')->get()->count();
if ($duplicados) {
    $errores[] = 'Códigos duplicados';
}
$salida = ['motor' => DB::connection()->getDriverName(), 'base' => DB::connection()->getDatabaseName(), 'titulos' => Libro::count(), 'objetivos' => count($filas), 'ejemplares' => DB::table('ejemplares')->count(), 'codigos_duplicados' => $duplicados, 'prestamos' => DB::table('prestamos')->count(), 'detalle_prestamo' => DB::table('detalle_prestamo')->count(), 'errores' => $errores, 'libros' => $filas];
file_put_contents(storage_path('app/private/inventario-aleatorio/verificacion.json'), json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode(array_diff_key($salida, ['libros' => true]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
if ($errores) {
    exit(1);
}
