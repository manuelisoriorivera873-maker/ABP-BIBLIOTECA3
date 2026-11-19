<?php

use App\Models\CuentaAcceso;
use App\Services\CatalogoLector;
use Database\Seeders\EjemplaresDemostracionSeeder;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->instance('request', Request::create('/'));
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
if (! app()->environment(['local', 'development']) || DB::connection()->getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'biblioteca2') {
    throw new RuntimeException('Esta verificación requiere biblioteca2 local de desarrollo.');
}
$isbn = array_column(EjemplaresDemostracionSeeder::titulos(), 'isbn');
$cuenta = CuentaAcceso::where('rol', 'USUARIO')->where('activo', true)->whereNotNull('id_usuario')->firstOrFail();
$libros = app(CatalogoLector::class)->consulta()->whereIn('libros.isbn', $isbn)->get();
$resultado = [];
foreach ($libros as $libro) {
    $copia = $libro->ejemplares()->with(['estado', 'ubicacion'])->firstOrFail();
    Auth::forgetGuards();
    Auth::guard()->setUser($cuenta);
    $respuesta = $kernel->handle(Request::create('/lector/libros/'.$libro->ficha->publico, 'GET'));
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$respuesta->getContent());
    $xpath = new DOMXPath($dom);
    $botones = $xpath->query('//button[contains(normalize-space(.), "Agregar a mi solicitud")]');
    $habilitado = $botones->length === 1 && ! $botones->item(0)->hasAttribute('disabled');
    if ($respuesta->getStatusCode() !== 200 || $habilitado !== ($libro->disponibles > 0)) {
        throw new RuntimeException('No coincide la disponibilidad DEMO de '.$libro->titulo);
    }
    $resultado[] = ['titulo' => $libro->titulo, 'id_libro' => $libro->getKey(), 'id_ejemplar' => $copia->getKey(), 'codigo' => $copia->codigo_inventario, 'estado' => $copia->estado->nombre_estado, 'ubicacion' => $copia->ubicacion->nombre_ubicacion, 'disponibles' => $libro->disponibles, 'boton_habilitado' => $habilitado];
}
if (count($resultado) !== count($isbn)) {
    throw new RuntimeException('Faltan títulos del conjunto revisado.');
}
file_put_contents(storage_path('app/private/ejemplares-demo/verificacion-'.date('Ymd-His').'.json'), json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo count($resultado).' títulos con ejemplares; '.count(array_filter($resultado, fn ($r) => $r['disponibles'] > 0)).' disponibles. Todas las fichas HTTP 200 y botones coherentes con existencias.'.PHP_EOL;
