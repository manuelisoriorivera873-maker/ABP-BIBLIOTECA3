<?php

namespace Tests\Feature;

use App\Models\CuentaAcceso;
use App\Models\Persona;
use App\Support\PermisosBiblioteca;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;

class RbacYPaginacionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        DB::unprepared(file_get_contents(__DIR__.'/../Fixtures/schema.sql'));
        $migracion = require database_path('migrations/2026_10_22_000002_create_permisos_por_rol.php');
        $migracion->up();
        DB::table('estados_usuario')->insert(['id_estado_usuario' => 1, 'nombre_estado_usuario' => 'ACTIVO']);
    }

    private function cuenta(string $rol): CuentaAcceso
    {
        return CuentaAcceso::create(['nombre_usuario' => 'cuenta.'.CuentaAcceso::count(), 'password' => 'UnaClaveSegura123!', 'rol' => $rol, 'activo' => true]);
    }

    private function casillas(string $html): array
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $consulta = new \DOMXPath($dom);
        $resultado = [];
        foreach ($consulta->query('//input[@type="checkbox" and @name="permisos[]"]') as $input) {
            $resultado[$input->getAttribute('value')] = ['checked' => $input->hasAttribute('checked'), 'disabled' => $input->hasAttribute('disabled')];
        }

        return $resultado;
    }

    public function test_todas_las_casillas_de_bibliotecario_se_editan_persisten_y_recargan(): void
    {
        $admin = $this->cuenta('SUPERADMIN');
        $a = $this->cuenta('BIBLIOTECARIO');
        $b = $this->cuenta('BIBLIOTECARIO');
        $url = '/cuentas/roles/BIBLIOTECARIO/permisos';
        $this->actingAs($admin);
        $casillas = $this->casillas($this->get($url)->assertOk()->getContent());
        $todos = [];
        foreach (PermisosBiblioteca::MODULOS as $modulo => $etiqueta) {
            foreach (PermisosBiblioteca::ACCIONES as $accion) {
                $permiso = $modulo.':'.$accion;
                $todos[] = $permiso;
                $this->assertFalse($casillas[$permiso]['disabled'], $permiso);
            }
        }
        $this->put($url, ['permisos' => $todos])->assertRedirect($url);
        $this->assertSame(count($todos), DB::table('rol_permisos')->where('rol', 'BIBLIOTECARIO')->count());
        $casillas = $this->casillas($this->get($url)->assertOk()->getContent());
        foreach ($todos as $permiso) {
            [$modulo, $accion] = explode(':', $permiso);
            $this->assertTrue($casillas[$permiso]['checked'], $permiso);
            $this->assertTrue($a->puede($modulo, $accion));
            $this->assertTrue($b->puede($modulo, $accion));
        }
        // El navegador no envía permisos[] cuando todas las casillas están desmarcadas.
        $this->put($url, [])->assertRedirect($url);
        $this->assertSame(0, DB::table('rol_permisos')->where('rol', 'BIBLIOTECARIO')->count());
        $casillas = $this->casillas($this->get($url)->assertOk()->getContent());
        foreach ($todos as $permiso) {
            [$modulo, $accion] = explode(':', $permiso);
            $this->assertFalse($casillas[$permiso]['checked']);
            $this->assertFalse($a->puede($modulo, $accion));
            $this->assertFalse($b->puede($modulo, $accion));
        }
        $this->assertTrue($admin->puede('personas', 'editar'));
        $this->get('/cuentas')->assertOk();
        $this->put('/cuentas/roles/SUPERADMIN/permisos', [])->assertNotFound();
    }

    public function test_casillas_de_lector_son_configurables_y_old_input_no_oculta_matriz_ajena(): void
    {
        $this->actingAs($this->cuenta('SUPERADMIN'));
        $url = '/cuentas/roles/USUARIO/permisos';
        $casillas = $this->casillas($this->get($url)->assertOk()->getContent());
        foreach ($casillas as $valor => $estado) {
            [$modulo, $accion] = explode(':', $valor);
            $this->assertSame(! PermisosBiblioteca::permiteRol('USUARIO', $modulo, $accion), $estado['disabled']);
        }
        $this->put($url, ['permisos' => ['cuentas:editar']])->assertSessionHasErrors('permisos');
        $casillas = $this->casillas($this->get('/cuentas/roles/BIBLIOTECARIO/permisos')->assertOk()->getContent());
        $this->assertTrue($casillas['libros:ver']['checked']);
        $this->assertTrue($casillas['libros:crear']['checked']);
        $this->withSession(['_old_input' => ['nombre' => 'Error en otra pantalla']]);
        $casillas = $this->casillas($this->get($url)->assertOk()->getContent());
        $this->assertTrue($casillas['libros:ver']['checked']);
        $this->assertFalse($casillas['libros:editar']['checked']);
    }

    public function test_consultas_configurables_del_lector_persisten_y_no_modifican_bibliotecarios(): void
    {
        $admin = $this->cuenta('SUPERADMIN');
        $lectores = [$this->cuenta('USUARIO'), $this->cuenta('USUARIO')];
        $bibliotecario = $this->cuenta('BIBLIOTECARIO');
        $url = '/cuentas/roles/USUARIO/permisos';
        $bibliotecarioAntes = PermisosBiblioteca::vigentes('BIBLIOTECARIO');
        $rutas = ['libros' => '/libros', 'autores' => '/autores', 'categorias' => '/catalogos/categorias', 'editoriales' => '/catalogos/editoriales'];

        foreach ($rutas as $modulo => $ruta) {
            $this->actingAs($admin)->put($url, ['permisos' => [$modulo.':ver']])->assertRedirect($url);
            $this->assertSame([$modulo.':ver'], PermisosBiblioteca::vigentes('USUARIO'));
            $this->assertSame($bibliotecarioAntes, PermisosBiblioteca::vigentes('BIBLIOTECARIO'));
            $casillas = $this->casillas($this->get($url)->assertOk()->getContent());
            foreach ($rutas as $otroModulo => $otraRuta) {
                $this->assertFalse($casillas[$otroModulo.':ver']['disabled']);
                $this->assertSame($otroModulo === $modulo, $casillas[$otroModulo.':ver']['checked']);
            }
            foreach ($lectores as $lector) {
                $this->actingAs($lector)->get($ruta)->assertOk();
                foreach ($rutas as $otroModulo => $otraRuta) {
                    if ($otroModulo !== $modulo) {
                        $this->get($otraRuta)->assertForbidden();
                    }
                }
                $this->get('/cuentas/roles/USUARIO/permisos')->assertForbidden();
                $this->post('/prestamos', [])->assertForbidden();
            }
        }

        $this->actingAs($admin)->put($url, [])->assertRedirect($url);
        $this->assertSame([], PermisosBiblioteca::vigentes('USUARIO'));
        $casillas = $this->casillas($this->get($url)->assertOk()->getContent());
        foreach ($rutas as $modulo => $ruta) {
            $this->assertFalse($casillas[$modulo.':ver']['checked']);
            foreach ($lectores as $lector) {
                $this->assertFalse($lector->puede($modulo, 'ver'));
                $this->actingAs($lector)->get($ruta)->assertForbidden();
            }
            $this->assertTrue($bibliotecario->puede($modulo, 'ver'));
        }
        $nuevoLector = $this->cuenta('USUARIO');
        $this->assertFalse($nuevoLector->puede('libros', 'ver'));
        $this->assertSame($bibliotecarioAntes, PermisosBiblioteca::vigentes('BIBLIOTECARIO'));
    }

    public function test_usuario_configura_permisos_extra_sin_adquirir_administracion_de_acceso(): void
    {
        $admin = $this->cuenta('SUPERADMIN');
        $a = $this->cuenta('USUARIO');
        $b = $this->cuenta('USUARIO');
        $url = '/cuentas/roles/USUARIO/permisos';
        $this->actingAs($admin);
        $casillas = $this->casillas($this->get($url)->assertOk()->getContent());
        $this->assertCount(count(PermisosBiblioteca::MODULOS) * count(PermisosBiblioteca::ACCIONES), $casillas);
        foreach ($casillas as $valor => $estado) {
            $this->assertFalse($estado['disabled'], $valor);
        }
        $antesBibliotecario = PermisosBiblioteca::vigentes('BIBLIOTECARIO');
        $this->put($url, ['permisos' => ['libros:crear', 'libros:editar', 'libros:eliminar', 'personas:editar']])->assertRedirect($url);
        $almacenados = PermisosBiblioteca::vigentes('USUARIO');
        $casillas = $this->casillas($this->get($url)->assertOk()->getContent());
        foreach ($casillas as $valor => $estado) {
            $this->assertSame(in_array($valor, $almacenados, true), $estado['checked'], $valor);
        }
        foreach ([$a, $b] as $lector) {
            $this->assertTrue($lector->puede('libros', 'crear'));
            $this->assertTrue($lector->puede('libros', 'editar'));
            $this->assertTrue($lector->puede('libros', 'eliminar'));
            $this->actingAs($lector)->get('/libros/create')->assertOk();
            $this->get('/cuentas')->assertForbidden();
            $this->put($url, ['permisos' => []])->assertForbidden();
            $persona = Persona::create(['nombre' => 'Prueba', 'apellido_paterno' => 'Sin', 'apellido_materno' => 'Escalada']);
            $this->put('/personas/'.$persona->id_persona, ['nombre' => 'Prueba', 'apellido_paterno' => 'Sin', 'apellido_materno' => 'Escalada', 'acceso' => ['gestionar' => 1, 'rol' => 'SUPERADMIN']])->assertForbidden();
        }
        $nuevo = $this->cuenta('USUARIO');
        $this->assertTrue($nuevo->puede('libros', 'crear'));
        $this->actingAs($admin)->put('/cuentas/roles/BIBLIOTECARIO/permisos', ['permisos' => ['autores:ver']])->assertRedirect();
        $this->assertSame($almacenados, PermisosBiblioteca::vigentes('USUARIO'));
        $this->put($url, ['permisos' => ['libros:ver']])->assertRedirect();
        foreach ([$a, $b, $nuevo] as $lector) {
            $this->assertFalse($lector->puede('libros', 'crear'));
            $this->assertFalse($lector->puede('libros', 'editar'));
            $this->assertFalse($lector->puede('libros', 'eliminar'));
            $this->actingAs($lector)->get('/libros/create')->assertForbidden();
        }
        $this->assertSame(['autores:ver'], PermisosBiblioteca::vigentes('BIBLIOTECARIO'));
        $this->assertNotSame($antesBibliotecario, PermisosBiblioteca::vigentes('BIBLIOTECARIO'));
        $this->assertSame(0, DB::table('cuenta_permisos')->count());
    }

    public function test_lectores_con_permiso_consultan_solo_sus_prestamos_y_devoluciones(): void
    {
        $admin = $this->cuenta('SUPERADMIN');
        $this->datosParaListados($admin);
        $this->actingAs($admin)->put('/cuentas/roles/USUARIO/permisos', ['permisos' => ['prestamos:ver', 'prestamos:crear', 'prestamos:editar', 'prestamos:eliminar', 'devoluciones:ver', 'devoluciones:crear', 'devoluciones:editar', 'devoluciones:eliminar', 'personas:ver', 'usuarios:ver', 'bibliotecarios:ver']])->assertRedirect();
        $lector = CuentaAcceso::where('id_usuario', 1)->firstOrFail();
        $this->actingAs($lector);
        $prestamos = $this->get('/prestamos')->assertOk()->viewData('prestamos');
        $this->assertSame([1], $prestamos->pluck('id_usuario')->all());
        $this->assertSame(1, $prestamos->total());
        $respuesta = $this->get('/devoluciones')->assertOk();
        $this->assertSame(1, $respuesta->viewData('devoluciones')->total());
        $this->assertSame(1, $respuesta->viewData('devoluciones')->first()->id_prestamo);
        $this->assertSame(0, $respuesta->viewData('pendientes')->total());
        $usuarios = $this->get('/usuarios')->assertOk()->viewData('usuarios');
        $this->assertSame(1, $usuarios->firstWhere('id_usuario', 1)->prestamos_count);
        $this->assertSame(0, $usuarios->firstWhere('id_usuario', 2)->prestamos_count);
        $personal = $this->get('/bibliotecarios')->assertOk()->viewData('bibliotecarios');
        $this->assertSame(0, $personal->firstWhere('id_bibliotecario', 2)->prestamos_count);
        $otraPersona = $this->get('/personas/2')->assertOk()->viewData('persona');
        $this->assertCount(0, $otraPersona->usuario->prestamos);
        $this->assertCount(0, $otraPersona->bibliotecario->prestamos);
        $this->get('/prestamos?buscar=Registro-32')->assertOk()->assertViewHas('prestamos', fn ($p) => $p->total() === 0);
        $this->post('/prestamos', [])->assertForbidden();
        $this->put('/prestamos/1', [])->assertForbidden();
        $this->delete('/prestamos/1')->assertForbidden();
        $this->post('/devoluciones/1/registrar', [])->assertForbidden();
        $this->put('/devoluciones/1', [])->assertForbidden();
        $this->delete('/devoluciones/1')->assertForbidden();
        $sinLector = $this->cuenta('USUARIO');
        $this->actingAs($sinLector)->get('/prestamos')->assertOk()->assertViewHas('prestamos', fn ($p) => $p->total() === 0);
        $this->get('/devoluciones')->assertOk()->assertViewHas('devoluciones', fn ($p) => $p->total() === 0);
    }

    public function test_matriz_institucional_se_comparte_y_archivo_individual_no_autoriza(): void
    {
        $a = $this->cuenta('BIBLIOTECARIO');
        $b = $this->cuenta('BIBLIOTECARIO');
        $a->permisos()->create(['modulo' => 'libros', 'accion' => 'eliminar']);
        $a->load('permisos');
        foreach (PermisosBiblioteca::MODULOS as $modulo => $etiqueta) {
            foreach (PermisosBiblioteca::ACCIONES as $accion) {
                $esperado = in_array($modulo.':'.$accion, PermisosBiblioteca::iniciales('BIBLIOTECARIO'), true);
                $this->assertSame($esperado, $a->puede($modulo, $accion));
                $this->assertSame($a->puede($modulo, $accion), $b->puede($modulo, $accion));
            }
        }
        $this->assertSame(1, $a->permisos()->count());
        $this->assertFalse($a->puede('libros', 'eliminar'));
        $this->assertFalse($a->puede('bibliotecarios', 'crear'));
        $this->assertFalse($a->puede('devoluciones', 'editar'));
    }

    public function test_cambios_de_rol_se_aplican_a_todos_sin_cache_y_con_auditoria(): void
    {
        $admin = $this->cuenta('SUPERADMIN');
        $a = $this->cuenta('BIBLIOTECARIO');
        $b = $this->cuenta('BIBLIOTECARIO');
        $url = '/cuentas/roles/BIBLIOTECARIO/permisos';
        $this->actingAs($admin)->get($url)->assertOk()->assertSee('Administración de permisos por rol');
        $this->put($url, ['permisos' => ['libros:crear', 'libros:eliminar']])->assertRedirect($url);
        $nuevo = $this->cuenta('BIBLIOTECARIO');
        foreach ([$a, $b, $nuevo] as $cuenta) {
            $this->assertTrue($cuenta->puede('libros', 'ver'));
            $this->assertTrue($cuenta->puede('libros', 'eliminar'));
            $this->actingAs($cuenta)->get('/libros/create')->assertOk();
            $this->put($url, ['permisos' => []])->assertForbidden();
            $this->get('/cuentas/roles/USUARIO/permisos')->assertForbidden();
        }
        $libro = DB::table('libros')->insertGetId(['isbn' => 'RBAC', 'titulo' => 'Libro RBAC']);
        $this->actingAs($admin)->put($url, ['permisos' => ['libros:ver']])->assertRedirect();
        foreach ([$a, $b, $nuevo] as $cuenta) {
            // Se reutilizan los mismos objetos: ni la sesión ni permisos cargados guardan privilegios.
            $this->assertFalse($cuenta->puede('libros', 'eliminar'));
            $this->actingAs($cuenta)->get('/libros/create')->assertForbidden();
            $this->delete('/libros/'.$libro)->assertForbidden();
        }
        $this->assertSame(0, DB::table('cuenta_permisos')->count());
        $cambio = DB::table('historial_rol_permisos')->orderByDesc('id_cambio')->first();
        $this->assertSame($admin->id_cuenta, $cambio->actor_id);
        $this->assertSame('BIBLIOTECARIO', $cambio->rol);
        $this->assertSame(['libros:ver'], json_decode($cambio->detalle, true)['nuevos']);
        $this->actingAs($admin)->put('/cuentas/'.$a->id_cuenta.'/permisos', ['permisos' => ['libros:eliminar']])->assertForbidden();
        $this->get('/cuentas/'.$a->id_cuenta.'/permisos')->assertRedirect($url);
        $this->put('/cuentas/roles/USUARIO/permisos', ['permisos' => ['cuentas:editar']])->assertSessionHasErrors('permisos');
        $this->get('/cuentas/roles/SUPERADMIN/permisos')->assertNotFound();
    }

    public function test_matriz_restringe_lector_y_rollback_conserva_permisos_del_rol(): void
    {
        $admin = $this->cuenta('SUPERADMIN');
        $lector = $this->cuenta('USUARIO');
        $antes = PermisosBiblioteca::vigentes('BIBLIOTECARIO');
        DB::statement("CREATE TRIGGER falla_rol_historial BEFORE INSERT ON historial_rol_permisos BEGIN SELECT RAISE(ABORT, 'fallo simulado'); END");
        $this->actingAs($admin)->put('/cuentas/roles/BIBLIOTECARIO/permisos', ['permisos' => []])->assertServerError();
        $this->assertSame($antes, PermisosBiblioteca::vigentes('BIBLIOTECARIO'));
        $lector->permisos()->create(['modulo' => 'personas', 'accion' => 'editar']);
        DB::table('rol_permisos')->insert(['rol' => 'USUARIO', 'modulo' => 'cuentas', 'accion' => 'editar']);
        $this->assertFalse($lector->puede('cuentas', 'editar'));
        $this->actingAs($lector)->get('/personas')->assertForbidden();
        $this->post('/prestamos', [])->assertForbidden();
        $this->put('/cuentas/roles/USUARIO/permisos', ['permisos' => []])->assertForbidden();
    }

    private function datosParaListados(CuentaAcceso $admin): void
    {
        // Inserción deliberadamente descendente; nombres inversos al ID.
        for ($id = 32; $id >= 1; $id--) {
            $nombre = 'Registro '.(33 - $id);
            DB::table('personas')->insert(['id_persona' => $id, 'nombre' => $nombre, 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura']);
            DB::table('usuarios')->insert(['id_usuario' => $id, 'id_persona' => $id, 'telefono' => '5500'.str_pad((string) $id, 6, '0', STR_PAD_LEFT), 'id_estado_usuario' => 1]);
            DB::table('bibliotecarios')->insert(['id_bibliotecario' => $id, 'id_persona' => $id]);
            DB::table('autores')->insert(['id_autor' => $id, 'id_persona' => $id]);
            DB::table('libros')->insert(['id_libro' => $id, 'isbn' => 'ISBN-'.$id, 'titulo' => $nombre]);
            DB::table('categorias')->insert(['id_categoria' => $id, 'nombre' => $nombre]);
            DB::table('editoriales')->insert(['id_editorial' => $id, 'nombre' => $nombre]);
            DB::table('ubicaciones_libros')->insert(['id_ubicacion' => $id, 'nombre_ubicacion' => $nombre]);
            DB::table('estados_ejemplar')->insert(['id_estado_ejemplar' => $id, 'nombre_estado' => $nombre]);
            DB::table('estados_usuario')->insert(['id_estado_usuario' => $id + 1, 'nombre_estado_usuario' => $nombre]);
            DB::table('ejemplares')->insert(['id_ejemplar' => $id, 'codigo_inventario' => 'Registro-'.$id, 'id_libro' => $id, 'id_ubicacion' => $id, 'id_estado_ejemplar' => $id]);
            DB::table('prestamos')->insert(['id_prestamo' => $id, 'id_usuario' => $id, 'id_bibliotecario' => $id, 'fecha_prestamo' => '2026-01-01', 'fecha_limite' => $id % 2 ? '2026-02-01' : '2099-01-01']);
            DB::table('detalle_prestamo')->insert(['id_detalle' => $id, 'id_prestamo' => $id, 'id_ejemplar' => $id]);
            if ($id <= 16) {
                DB::table('devoluciones')->insert(['id_devolucion' => $id, 'id_prestamo' => $id, 'fecha_devolucion' => '2026-01-02']);
                DB::table('detalle_devolucion')->insert(['id_detalle_devolucion' => $id, 'id_devolucion' => $id, 'id_detalle_prestamo' => $id]);
            }
            DB::table('cuentas_acceso')->insert(['id_cuenta' => $id + 1, 'nombre_usuario' => 'Registro.'.$id, 'password' => $admin->password, 'rol' => 'USUARIO', 'activo' => 1, 'id_usuario' => $id]);
            DB::table('historial_permisos')->insert(['id_cambio' => $id, 'actor_id' => $admin->id_cuenta, 'cuenta_id' => $id + 1, 'evento' => 'PRUEBA', 'detalle' => '{}']);
            DB::table('historial_rol_permisos')->insert(['actor_id' => $admin->id_cuenta, 'rol' => 'USUARIO', 'detalle' => '{}']);
        }
    }

    public function test_todos_los_listados_paginan_por_id_y_conservan_filtros(): void
    {
        $admin = $this->cuenta('SUPERADMIN');
        $this->datosParaListados($admin);
        $this->actingAs($admin);
        $listados = [
            ['/libros', 'libros', 'id_libro', 'page'],
            ['/autores', 'autores', 'id_autor', 'page'],
            ['/personas', 'personas', 'id_persona', 'page'],
            ['/usuarios', 'usuarios', 'id_usuario', 'page'],
            ['/bibliotecarios', 'bibliotecarios', 'id_bibliotecario', 'page'],
            ['/ejemplares', 'ejemplares', 'id_ejemplar', 'page'],
            ['/prestamos', 'prestamos', 'id_prestamo', 'page'],
            ['/devoluciones', 'pendientes', 'id_prestamo', 'pendientes_page'],
            ['/devoluciones', 'devoluciones', 'id_devolucion', 'historial_page'],
            ['/cuentas', 'cuentas', 'id_cuenta', 'page'],
            ['/cuentas/historial', 'cambios', 'id_cambio', 'cuentas_page'],
            ['/cuentas/historial', 'cambiosRoles', 'id_cambio', 'roles_page'],
        ];
        foreach (['categorias' => 'id_categoria', 'editoriales' => 'id_editorial', 'ubicaciones' => 'id_ubicacion', 'estados-usuario' => 'id_estado_usuario', 'estados-ejemplar' => 'id_estado_ejemplar'] as $modulo => $pk) {
            $listados[] = ['/catalogos/'.$modulo, 'registros', $pk, 'page'];
        }
        foreach ($listados as [$ruta, $variable, $pk, $pagina]) {
            $primera = $this->get($ruta.'?buscar=Registro')->assertOk()->assertSee('Mostrando')->assertDontSee('pagination.previous')->assertDontSee('pagination.next')->assertDontSee('Showing');
            $paginador = $primera->viewData($variable);
            $ids = collect($paginador->items())->pluck($pk)->map(fn ($id) => (int) $id)->all();
            $ordenados = $ids;
            sort($ordenados);
            $this->assertSame($ordenados, $ids, $ruta);
            $this->assertTrue($paginador->hasMorePages(), $ruta);
            $this->assertStringContainsString('buscar=Registro', $paginador->nextPageUrl(), $ruta);
            $segunda = $this->get($ruta.'?buscar=Registro&'.$pagina.'=2')->assertOk()->viewData($variable);
            $siguientes = collect($segunda->items())->pluck($pk)->map(fn ($id) => (int) $id)->all();
            $this->assertGreaterThan(max($ids), min($siguientes), $ruta);
            $this->assertStringContainsString('class="page-item active page-number"', $primera->getContent());
        }
        $respuesta = $this->get('/personas?buscar=Registro&page=2')->assertOk()->assertSee('Mostrando 16 a 30 de 32 registros');
        $this->assertStringContainsString('buscar=Registro', $respuesta->viewData('personas')->nextPageUrl());
        $this->assertStringContainsString('page=3', $respuesta->viewData('personas')->nextPageUrl());
        $this->get('/personas?buscar=Registro&page=3')->assertOk()->assertSee('Mostrando 31 a 32 de 32 registros')->assertSee('aria-disabled="true"', false);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }

    public function test_filtros_de_prestamos_y_paginadores_de_devoluciones_siguen_correctos(): void
    {
        $admin = $this->cuenta('SUPERADMIN');
        $this->datosParaListados($admin);
        $this->actingAs($admin);
        $this->assertSame(16, $this->get('/prestamos?estado=devueltos')->assertOk()->viewData('prestamos')->total());
        $this->assertSame(8, $this->get('/prestamos?estado=activos')->assertOk()->viewData('prestamos')->total());
        $this->assertSame(8, $this->get('/prestamos?estado=atrasados')->assertOk()->viewData('prestamos')->total());
        $respuesta = $this->get('/devoluciones?buscar=Registro&pendientes_page=2&historial_page=2')->assertOk();
        $this->assertSame(16, $respuesta->viewData('pendientes')->total());
        $this->assertSame(16, $respuesta->viewData('devoluciones')->total());
        $this->assertStringContainsString('buscar=Registro', $respuesta->viewData('pendientes')->previousPageUrl());
        $this->assertStringContainsString('historial_page=2', $respuesta->viewData('pendientes')->previousPageUrl());
    }
}
