<?php

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Respaldo completo, conteos, estabilidad de filas y checksum antes de migrar.
return function (string $etiqueta, array $metadatos = []): array {
    if (! preg_match('/^[a-z-]+$/', $etiqueta)) {
        throw new RuntimeException('Etiqueta de respaldo inválida.');
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
    $respaldo = $directorio.'/biblioteca2-'.$etiqueta.'-'.date('Ymd-His').'.sql';
    $opciones = $directorio.'/conexion-'.$etiqueta.'-'.bin2hex(random_bytes(8)).'.cnf';
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
    file_put_contents($respaldo.'.json', json_encode(['tablas' => $antes, 'sha256' => $checksum, 'metadatos' => $metadatos], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo 'Respaldo verificado: '.$respaldo.PHP_EOL;

    return compact('antes', 'instantanea', 'respaldo');
};
