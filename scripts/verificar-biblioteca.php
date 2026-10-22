<?php

// Auditoría de solo lectura: nunca autentica por contraseña ni modifica ultimo_acceso.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CuentaAcceso;
use App\Support\PermisosBiblioteca;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

config(['session.driver' => 'array', 'cache.default' => 'array']);
if (DB::connection()->getDriverName() !== 'mysql') {
    throw new RuntimeException('Esta auditoría requiere MariaDB/MySQL.');
}

$errores = 0;
$conteos = [];
foreach (DB::select('SHOW TABLES') as $fila) {
    $tabla = array_values((array) $fila)[0];
    $conteos[$tabla] = DB::table($tabla)->count();
}
echo json_encode(['base' => DB::connection()->getDatabaseName(), 'conteos' => $conteos], JSON_PRETTY_PRINT).PHP_EOL;

// Verificar todas las FK reales, incluyendo tablas históricas de préstamos y devoluciones.
$relaciones = DB::select('SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL');
foreach ($relaciones as $relacion) {
    $huerfanos = DB::table($relacion->TABLE_NAME.' as origen')
        ->leftJoin($relacion->REFERENCED_TABLE_NAME.' as destino', 'origen.'.$relacion->COLUMN_NAME, '=', 'destino.'.$relacion->REFERENCED_COLUMN_NAME)
        ->whereNotNull('origen.'.$relacion->COLUMN_NAME)
        ->whereNull('destino.'.$relacion->REFERENCED_COLUMN_NAME)->count();
    if ($huerfanos) {
        echo 'ERROR FK '.$relacion->TABLE_NAME.'.'.$relacion->COLUMN_NAME.': '.$huerfanos.PHP_EOL;
        $errores++;
    }
}
echo 'Relaciones verificadas: '.count($relaciones).PHP_EOL;

foreach (CuentaAcceso::get() as $cuenta) {
    $hashSeguro = in_array(password_get_info($cuenta->password)['algoName'], ['bcrypt', 'argon2i', 'argon2id'], true);
    if (! $hashSeguro || ! in_array($cuenta->rol, ['SUPERADMIN', 'BIBLIOTECARIO', 'USUARIO'], true)) {
        echo 'ERROR: hash o rol inválido en cuenta ID '.$cuenta->id_cuenta.PHP_EOL;
        $errores++;
    }
}
foreach (DB::table('rol_permisos')->get() as $permiso) {
    if (! PermisosBiblioteca::permiteRol($permiso->rol, $permiso->modulo, $permiso->accion)) {
        echo 'ERROR: permiso incompatible en rol '.$permiso->rol.PHP_EOL;
        $errores++;
    }
}

$admin = CuentaAcceso::where('rol', 'SUPERADMIN')->where('activo', true)->firstOrFail();
$kernel = $app->make(Kernel::class);
// Simular exclusivamente GET con el guard en memoria; no guardar sesiones reales.
$rutas = ['/', '/cuentas', '/cuentas/historial', '/libros', '/autores', '/personas', '/usuarios', '/usuarios/create', '/bibliotecarios', '/bibliotecarios/create', '/prestamos', '/devoluciones', '/ejemplares', '/solicitudes'];
$rutas = array_merge($rutas, ['/cuentas/roles/BIBLIOTECARIO/permisos', '/cuentas/roles/USUARIO/permisos', '/personas?page=2']);
foreach (['categorias', 'editoriales', 'ubicaciones', 'estados-usuario', 'estados-ejemplar'] as $catalogo) {
    $rutas[] = '/catalogos/'.$catalogo;
}
foreach (DB::table('personas')->pluck('id_persona') as $idPersona) {
    $rutas[] = '/personas/'.$idPersona.'/edit';
}
foreach (DB::table('cuentas_acceso')->pluck('id_cuenta') as $idCuenta) {
    $rutas[] = '/cuentas/'.$idCuenta.'/editar';
}
foreach ($rutas as $ruta) {
    Auth::forgetGuards();
    Auth::guard()->setUser($admin);
    $respuesta = $kernel->handle(Request::create($ruta, 'GET'));
    $codigo = $respuesta->getStatusCode();
    echo 'GET '.$ruta.' '.$codigo.PHP_EOL;
    if ($codigo !== 200) {
        $errores++;
    }
}
$lector = CuentaAcceso::where('rol', 'USUARIO')->where('activo', true)->whereNotNull('id_usuario')->firstOrFail();
foreach (['/libros', '/solicitudes/nueva', '/solicitudes', '/lector/prestamos'] as $ruta) {
    Auth::forgetGuards();
    Auth::guard()->setUser($lector);
    $respuesta = $kernel->handle(Request::create($ruta, 'GET'));
    echo 'GET lector '.$ruta.' '.$respuesta->getStatusCode().PHP_EOL;
    if ($respuesta->getStatusCode() !== 200) {
        $errores++;
    }
    if ($ruta === '/libros') {
        foreach (['Catálogo de libros', 'Solicitar préstamo', 'Mis solicitudes', 'Mis préstamos'] as $enlace) {
            if (! str_contains($respuesta->getContent(), $enlace)) {
                echo 'ERROR: falta enlace '.$enlace.PHP_EOL;
                $errores++;
            }
        }
    }
}
$personal = CuentaAcceso::where('rol', 'BIBLIOTECARIO')->where('activo', true)->firstOrFail();
Auth::forgetGuards();
Auth::guard()->setUser($personal);
$respuesta = $kernel->handle(Request::create('/', 'GET'));
echo 'GET escritorio bibliotecario '.$respuesta->getStatusCode().PHP_EOL;
if ($respuesta->getStatusCode() !== 200 || ! str_contains($respuesta->getContent(), 'Solicitudes de préstamo')) {
    $errores++;
}
foreach ($conteos as $tabla => $cantidad) {
    if (DB::table($tabla)->count() !== $cantidad) {
        echo 'ERROR: cambió el conteo de '.$tabla.PHP_EOL;
        $errores++;
    }
}
echo 'Errores: '.$errores.PHP_EOL;
exit($errores ? 1 : 0);
