<?php

use App\Support\PermisosBiblioteca;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Solo CLI. Sin --apply únicamente inspecciona y documenta las diferencias.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

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

$respaldoVerificado = require __DIR__.'/respaldo-verificado.php';
extract($respaldoVerificado('rbac', ['diferencias_permisos' => $diferencias]));

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
