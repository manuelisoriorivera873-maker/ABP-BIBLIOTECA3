<?php

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
    throw new RuntimeException('Se requiere la conexión verificada a biblioteca2.');
}
foreach (['solicitudes_prestamo', 'solicitud_detalles', 'sesiones_operativas'] as $tabla) {
    if (Schema::hasTable($tabla)) {
        throw new RuntimeException('La instalación existe o es parcial. No se ha modificado nada.');
    }
}
if (! Schema::hasTable('rol_permisos')) {
    throw new RuntimeException('Primero debe estar instalado el RBAC por rol.');
}
echo 'Migración aditiva: solicitudes, detalles, sesiones operativas y cuatro permisos de rol. Sin préstamos ni cuentas de prueba en biblioteca2.'.PHP_EOL;
if (! in_array('--apply', $argv, true)) {
    exit;
}
$permisosAntes = DB::table('rol_permisos')->orderBy('id_permiso')->get()->toArray();
$historialAntes = DB::table('historial_rol_permisos')->orderBy('id_cambio')->get()->toArray();
$respaldoVerificado = require __DIR__.'/respaldo-verificado.php';
$respaldo = $respaldoVerificado('solicitudes');
if (Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_22_000003_create_solicitudes_prestamo.php', '--force' => true]) !== 0) {
    throw new RuntimeException('Migración no completada. Conserva el respaldo y revisa el esquema antes de reintentar.');
}
$despues = ($respaldo['instantanea'])();
foreach ($respaldo['antes'] as $tabla => $estado) {
    if (! in_array($tabla, ['migrations', 'rol_permisos', 'historial_rol_permisos'], true) && $despues[$tabla] !== $estado) {
        throw new RuntimeException('Cambió una tabla histórica: '.$tabla.'. Requiere revisión.');
    }
}
foreach (['rol_permisos' => $permisosAntes, 'historial_rol_permisos' => $historialAntes] as $tabla => $filas) {
    $clave = $tabla === 'rol_permisos' ? 'id_permiso' : 'id_cambio';
    foreach ($filas as $fila) {
        if ((array) DB::table($tabla)->where($clave, $fila->$clave)->first() !== (array) $fila) {
            throw new RuntimeException('Se alteró una fila histórica de '.$tabla.'.');
        }
    }
}
echo 'Solicitudes instaladas. Filas históricas verificadas sin cambios; permisos nuevos centralizados por rol.'.PHP_EOL;
