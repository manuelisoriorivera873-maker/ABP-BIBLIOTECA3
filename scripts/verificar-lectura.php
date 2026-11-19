<?php

use App\Models\Autor;
use App\Models\CuentaAcceso;
use App\Models\Ejemplar;
use App\Models\Libro;
use App\Models\RecursoDigital;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Auditoría HTTP y SQL de solo lectura; no inicia sesiones por contraseña.
if (PHP_SAPI !== 'cli') {
    exit;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::connection()->getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'biblioteca2') {
    throw new RuntimeException('Solo auditoría local biblioteca2.');
}
config(['session.driver' => 'array', 'cache.default' => 'array']);
$datos = json_decode(file_get_contents(database_path('data/lectura-catalogo.json')), true);
$salida = ['libros' => Libro::count(), 'ejemplares' => Ejemplar::count(), 'prestamos' => DB::table('prestamos')->count(), 'detalles' => DB::table('detalle_prestamo')->count(), 'autores' => Autor::count(), 'idiomas' => DB::table('libro_fichas')->selectRaw('idioma, count(*) as cantidad')->groupBy('idioma')->get()->all(), 'libros_nuevos' => [], 'recursos' => [], 'errores' => []];
$lector = CuentaAcceso::where('rol', 'USUARIO')->where('activo', true)->whereNotNull('id_usuario')->firstOrFail();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$get = function (string $ruta) use ($kernel, $lector) {
    Auth::forgetGuards();
    Auth::guard('web')->setUser($lector);
    $q = Request::create($ruta);
    $r = $kernel->handle($q);
    $kernel->terminate($q, $r);

    return $r;
};
foreach ($datos['libros'] as $d) {
    $l = Libro::where('isbn', $d['isbn'])->sole();
    $salida['libros_nuevos'][] = ['titulo' => $l->titulo, 'isbn' => $l->isbn, 'ejemplares' => $l->ejemplares()->count(), 'idioma' => $l->ficha->idioma];
    if ($l->ficha->idioma !== 'spa' || $get('/lector/libros/'.$l->ficha->publico)->getStatusCode() !== 200) {
        $salida['errores'][] = $l->titulo;
    }
}
foreach (RecursoDigital::all() as $r) {
    $esperado = $r->tipo === 'integrado' ? 200 : 302;
    $status = $get('/lector/leer/'.$r->publico)->getStatusCode();
    $salida['recursos'][] = ['edicion' => $r->edicion, 'tipo' => $r->tipo, 'accesible' => $r->accesible, 'http' => $status];
    if (! $r->accesible || $status !== $esperado) {
        $salida['errores'][] = 'Recurso '.$r->publico;
    }
}
if ($get('/lector/favoritos')->getStatusCode() !== 200) {
    $salida['errores'][] = 'Favoritos';
}
foreach (['cuentas_acceso', 'rol_permisos', 'prestamos', 'detalle_prestamo', 'devoluciones', 'detalle_devolucion', 'ejemplares'] as $t) {
    $salida['protegidas'][$t] = DB::table($t)->count();
}
file_put_contents(storage_path('app/private/digital/auditoria-final.json'), json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode(array_diff_key($salida, array_flip(['libros_nuevos', 'recursos'])), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
if ($salida['errores']) {
    exit(1);
}
