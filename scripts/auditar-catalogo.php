<?php

use App\Models\Libro;
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
$fuentes = require __DIR__.'/../database/data/bibliografia.php';
$normalizar = fn ($s) => Str::lower(Str::ascii($s));
$registros = [];
foreach (Libro::with(['autores.persona', 'editoriales', 'categorias'])->withCount('ejemplares')->orderBy('id_libro')->get() as $libro) {
    $obra = collect($fuentes['obras'])->first(fn ($o) => $normalizar($o['titulo']) === $normalizar($libro->titulo));
    $isbn = preg_replace('/[^0-9X]/', '', strtoupper($libro->isbn));
    $checksum = strlen($isbn) === 13 && ctype_digit($isbn) && array_sum(array_map(fn ($i) => (int) $isbn[$i] * ($i % 2 ? 3 : 1), range(0, 12))) % 10 === 0;
    $registros[] = [
        'id_libro' => $libro->getKey(), 'titulo' => $libro->titulo, 'isbn_actual' => $libro->isbn,
        'isbn_checksum_valido' => $checksum, 'edicion_verificada' => false,
        'autores_registrados' => $libro->autores->map->nombre_completo->all(),
        'obra_y_autoria_fuente' => $obra,
        'editoriales_registradas' => $libro->editoriales->pluck('nombre')->all(),
        'ejemplares' => $libro->ejemplares_count,
        'detalles_prestamo' => DB::table('detalle_prestamo')->join('ejemplares', 'ejemplares.id_ejemplar', '=', 'detalle_prestamo.id_ejemplar')->where('ejemplares.id_libro', $libro->getKey())->count(),
        'revision' => $obra ? 'Obra auténtica; edición física, ISBN y editorial pendientes de cotejo. No sustituir por otro ISBN.' : 'Sin correspondencia bibliográfica confirmada. No se considera ficticio solo por estar incompleto.',
    ];
}
$duplicados = DB::table('libros')->select('isbn')->selectRaw('COUNT(*) as cantidad')->groupBy('isbn')->havingRaw('COUNT(*) > 1')->get();
$directorio = storage_path('app/private/auditorias');
if (! is_dir($directorio)) {
    mkdir($directorio, 0700, true);
}
$ruta = $directorio.'/catalogo-'.date('Ymd-His').'.json';
file_put_contents($ruta, json_encode(['registros' => $registros, 'isbn_duplicados' => $duplicados], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Auditoría de solo lectura: '.$ruta.PHP_EOL;
echo 'Registros: '.count($registros).'; obras contrastadas: '.collect($registros)->whereNotNull('obra_y_autoria_fuente')->count().'; ISBN duplicados: '.$duplicados->count().PHP_EOL;
