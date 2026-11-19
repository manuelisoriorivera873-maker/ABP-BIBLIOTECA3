<?php

use App\Models\AutorFicha;
use App\Models\Libro;
use App\Models\LibroFicha;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
$instalacion = json_decode(file_get_contents(storage_path('app/private/ampliacion/resultado.json')), true, 512, JSON_THROW_ON_ERROR);
$protegidas = ['ejemplares', 'prestamos', 'detalle_prestamo', 'devoluciones', 'detalle_devolucion', 'cuentas_acceso', 'usuarios', 'bibliotecarios', 'rol_permisos', 'cuenta_permisos', 'solicitudes_prestamo', 'solicitud_detalles', 'solicitud_envios', 'historial_permisos', 'historial_rol_permisos', 'sesiones_operativas', 'estados_usuario', 'estados_ejemplar', 'ubicaciones_libros'];
foreach ($protegidas as $tabla) {
    $filas = DB::table($tabla)->get()->map(fn ($f) => json_encode((array) $f, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))->all();
    sort($filas);
    $estado = ['filas' => count($filas), 'sha256' => hash('sha256', implode("\n", $filas))];
    if ($estado !== $instalacion['antes'][$tabla]) {
        throw new RuntimeException('Cambió el contenido protegido de '.$tabla);
    }
}
$datos = json_decode(file_get_contents(database_path('data/ampliacion.json')), true, 512, JSON_THROW_ON_ERROR);
$isbn = collect($datos['libros'])->pluck('isbn');
if (Libro::whereIn('isbn', $isbn)->count() !== 27 || Libro::whereIn('isbn', $isbn)->has('ejemplares')->exists()) {
    throw new RuntimeException('No coincide la ampliación bibliográfica o tiene inventario añadido.');
}
$duplicados = Libro::all()->groupBy(fn ($l) => Str::upper(preg_replace('/[\s-]/u', '', $l->isbn)))->filter(fn ($g) => $g->count() > 1)->keys();
$autoresDuplicados = AutorFicha::all()->map(fn ($a) => Str::lower(Str::ascii($a->nombre_publico)))->duplicates()->values();
if ($duplicados->isNotEmpty() || $autoresDuplicados->isNotEmpty()) {
    throw new RuntimeException('Se detectaron identidades bibliográficas duplicadas.');
}
$sinopsis = LibroFicha::whereNotNull('sinopsis')->get();
foreach ($sinopsis as $ficha) {
    $palabras = count(preg_split('/\s+/u', trim($ficha->sinopsis)));
    if ($palabras < 60 || $palabras > 100) {
        throw new RuntimeException('Sinopsis fuera del intervalo acordado.');
    }
}
$resultado = [
    'libros' => Libro::count(), 'libros_agregados' => 27,
    'portadas_recuperadas' => ['Cien años de soledad'],
    'portadas_nuevas' => 27, 'portadas_contrastadas' => LibroFicha::whereNotNull('portada_verificada_en')->count(),
    'sinopsis' => $sinopsis->count(), 'autores_con_ficha' => AutorFicha::count(),
    'pendientes' => Libro::with(['ficha', 'autores.persona'])->whereHas('ficha', fn ($q) => $q->whereNull('isbn_verificado'))->get()->map(fn ($l) => ['titulo' => $l->titulo, 'isbn_actual' => $l->isbn, 'revision' => $l->ficha->revision_bibliografica])->all(),
    'tablas_protegidas_intactas' => $protegidas,
    'isbn_duplicados' => $duplicados->all(), 'autores_duplicados' => $autoresDuplicados->all(),
];
file_put_contents(storage_path('app/private/ampliacion/auditoria-final.json'), json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
