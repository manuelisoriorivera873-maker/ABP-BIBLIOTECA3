<?php

// Solo CLI. Sin --apply únicamente inspecciona y documenta las diferencias.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Support\PermisosBiblioteca;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

if (DB::connection()->getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'biblioteca2') {
    throw new RuntimeException('Se requiere la conexión verificada a biblioteca2.');
}
if (Schema::hasTable('rol_permisos') || Schema::hasTable('historial_rol_permisos')) {
    throw new RuntimeException('Hay tablas RBAC existentes: inspecciona la instalación antes de repetirla. No se cambió nada.');
}
$diferencias = [];
foreach (DB::table('cuentas_acceso')->orderBy('id_cuenta')->get(['id_cuenta', 'rol']) as $cuenta) {
    $anteriores = DB::table('cuenta_permisos')->where('id_cuenta', $cuenta->id_cuenta)->get()
        ->map(fn ($p) => $p->modulo.':'.$p->accion)->all();
    $base = PermisosBiblioteca::iniciales($cuenta->rol);
    $diferencias[] = ['id_cuenta' => $cuenta->id_cuenta, 'rol' => $cuenta->rol,
        'solo_en_archivo_individual' => array_values(array_diff($anteriores, $base)),
        'nuevos_segun_matriz_institucional' => array_values(array_diff($base, $anteriores)),
    ];
}
echo json_encode($diferencias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
if (! in_array('--apply', $argv, true)) {
    echo 'Inspección terminada; los permisos individuales se conservarán, sin usarlos ni unirlos a la matriz institucional.'.PHP_EOL;
    exit;
}

$tablas = array_map(fn ($fila) => array_values((array) $fila)[0], DB::select('SHOW TABLES'));
$instantanea = function () use ($tablas): array {
    $estado = [];
    foreach ($tablas as $tabla) {
        $filas = DB::table($tabla)->get()->map(fn ($fila) => (array) $fila)->all();
        $serializadas = array_map(fn ($fila) => json_encode($fila, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $filas);
        sort($serializadas);
        $estado[$tabla] = ['filas' => count($filas), 'sha256' => hash('sha256', implode("\n", $serializadas))];
    }

    return $estado;
};
$antes = $instantanea();
$directorio = storage_path('app/private/backups');
if (! is_dir($directorio)) {
    mkdir($directorio, 0700, true);
}
$respaldo = $directorio.'/biblioteca2-rbac-'.date('Ymd-His').'.sql';
$opciones = $directorio.'/conexion-rbac-'.bin2hex(random_bytes(8)).'.cnf';
$conexion = DB::connection()->getConfig();
$escapar = fn ($valor) => '"'.str_replace(['\\', '"', "\r", "\n"], ['\\\\', '\\"', '\\r', '\\n'], (string) $valor).'"';
$configuracion = "[client]\n";
foreach (['host' => 'host', 'port' => 'port', 'user' => 'username', 'password' => 'password'] as $opcion => $campo) {
    $configuracion .= $opcion.'='.$escapar($conexion[$campo] ?? '')."\n";
}
if (file_put_contents($opciones, $configuracion) !== strlen($configuracion)) {
    throw new RuntimeException('No fue posible preparar la conexión privada para el respaldo.');
}
try {
    $dump = new Process(['C:\xampp\mysql\bin\mysqldump.exe', '--defaults-extra-file='.$opciones,
        '--single-transaction', '--skip-lock-tables', '--routines', '--events', '--triggers', '--hex-blob',
        '--complete-insert', '--skip-extended-insert', '--default-character-set=utf8mb4',
        '--result-file='.$respaldo, 'biblioteca2']);
    $dump->setTimeout(120);
    if ($dump->run() !== 0) {
        throw new RuntimeException('Falló mysqldump. No se aplicó la migración.');
    }
} finally {
    // Eliminar únicamente el archivo de opciones que acaba de crearse, nunca el respaldo.
    unlink($opciones);
}
$contenido = file_get_contents($respaldo);
if (! $contenido || ! str_contains($contenido, 'Dump completed')) {
    throw new RuntimeException('El respaldo está incompleto. No se aplicó la migración.');
}
foreach ($antes as $tabla => $estado) {
    if (! str_contains($contenido, 'CREATE TABLE `'.$tabla.'`')
        || preg_match_all('/^INSERT INTO `'.preg_quote($tabla, '/').'` /m', $contenido) !== $estado['filas']) {
        throw new RuntimeException('No coincide la estructura o el conteo respaldado de '.$tabla.'. No se aplicó la migración.');
    }
}
if ($antes !== $instantanea()) {
    throw new RuntimeException('Los datos cambiaron durante el respaldo. Repite durante un periodo sin escrituras.');
}
$checksum = hash('sha256', $contenido);
if (hash_file('sha256', $respaldo) !== $checksum) {
    throw new RuntimeException('Falló la verificación SHA-256 del respaldo.');
}
file_put_contents($respaldo.'.sha256', $checksum);
file_put_contents($respaldo.'.json', json_encode(['tablas' => $antes, 'sha256' => $checksum, 'diferencias_permisos' => $diferencias], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Respaldo verificado: '.$respaldo.PHP_EOL;

$salida = Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_22_000002_create_permisos_por_rol.php', '--force' => true]);
if ($salida !== 0) {
    throw new RuntimeException('Falló la migración incremental. Conserva el respaldo y revisa el esquema antes de reintentar.');
}
$despues = $instantanea();
foreach ($antes as $tabla => $estado) {
    if ($tabla !== 'migrations' && $despues[$tabla] !== $estado) {
        throw new RuntimeException('Cambió una tabla histórica: '.$tabla.'. Se requiere revisión.');
    }
}
echo 'RBAC instalado. Todas las filas históricas conservan sus huellas SHA-256; solo se agregó el registro de esta migración.'.PHP_EOL;
