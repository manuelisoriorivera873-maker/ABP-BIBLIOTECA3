<?php

namespace Tests\Feature;

use App\Models\Bibliotecario;
use App\Models\CuentaAcceso;
use App\Models\Libro;
use App\Models\Persona;
use App\Models\Usuario;
use App\Support\PermisosBiblioteca;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeguridadBibliotecaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        DB::unprepared(file_get_contents(__DIR__.'/../Fixtures/schema.sql'));
        $rbac = require database_path('migrations/2026_10_22_000002_create_permisos_por_rol.php');
        $rbac->up();
        DB::table('estados_usuario')->insert(['id_estado_usuario' => 1, 'nombre_estado_usuario' => 'ACTIVO']);
    }

    private function cuenta(string $rol = 'SUPERADMIN'): CuentaAcceso
    {
        return CuentaAcceso::create(['nombre_usuario' => 'cuenta'.CuentaAcceso::count(), 'password' => 'UnaClaveSegura123!', 'rol' => $rol, 'activo' => true]);
    }

    private function registro(): array
    {
        return ['nombre' => 'Lector', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Seguro', 'telefono' => '5512345678', 'nombre_usuario' => 'lector.nuevo', 'password' => 'UnaClaveSegura123!', 'password_confirmation' => 'UnaClaveSegura123!'];
    }

    private function asignarRol(CuentaAcceso $cuenta, string $rol)
    {
        $cuenta = $cuenta->fresh();
        $persona = $cuenta->lector?->persona ?? $cuenta->empleado?->persona;

        return $this->put('/personas/'.$persona->id_persona, $persona->only(['nombre', 'apellido_paterno', 'apellido_materno']) + [
            'acceso' => ['gestionar' => 1, 'cuenta_id' => $cuenta->id_cuenta, 'rol' => $rol, 'activo' => 1],
        ]);
    }

    public function test_login_destinos_estado_y_logout(): void
    {
        foreach (['SUPERADMIN' => '/', 'BIBLIOTECARIO' => '/', 'USUARIO' => '/libros'] as $rol => $destino) {
            $cuenta = $this->cuenta($rol);
            $this->withSession(['url.intended' => url('/registro')])->post('/login', ['nombre_usuario' => $cuenta->nombre_usuario, 'password' => 'UnaClaveSegura123!'])->assertRedirect($destino);
            $this->assertAuthenticatedAs($cuenta);
            $this->assertNotNull($cuenta->fresh()->ultimo_acceso);
            $this->get('/registro')->assertRedirect($destino);
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
        }
        $cuenta->update(['activo' => false]);
        $this->post('/login', ['nombre_usuario' => $cuenta->nombre_usuario, 'password' => 'UnaClaveSegura123!'])->assertSessionHasErrors('nombre_usuario');
        $this->assertGuest();
    }

    public function test_registro_ignora_privilegios_y_crea_relaciones(): void
    {
        $this->get('/login')->assertOk()->assertSee('registro');
        $this->get('/registro')->assertOk()->assertSee('Datos personales');
        $this->post('/registro', $this->registro() + ['rol' => 'SUPERADMIN', 'permisos' => ['libros:eliminar']])->assertRedirect('/libros');
        $cuenta = CuentaAcceso::firstOrFail();
        $this->assertSame('USUARIO', $cuenta->rol);
        $this->assertTrue(Hash::check('UnaClaveSegura123!', $cuenta->password));
        $this->assertSame('Lector', $cuenta->lector->persona->nombre);
        $this->assertCount(0, $cuenta->permisos);
        $this->assertCount(4, PermisosBiblioteca::vigentes('USUARIO'));
        $this->assertSame(1, DB::table('historial_permisos')->count());
        $this->get('/libros')->assertOk()->assertDontSee('Registrar libro');
        $this->get('/cuentas')->assertForbidden();
        $this->post('/libros', [])->assertForbidden();
        $this->put('/cuentas/'.$cuenta->id_cuenta, ['rol' => 'SUPERADMIN'])->assertForbidden();
    }

    public function test_validacion_duplicados_y_rollback(): void
    {
        $this->post('/registro', array_replace($this->registro(), ['password' => 'corta']))->assertSessionHasErrors('password');
        $this->assertSame(0, Persona::count());
        Persona::create(array_intersect_key($this->registro(), array_flip(['nombre', 'apellido_paterno', 'apellido_materno'])));
        $this->post('/registro', $this->registro())->assertSessionHasErrors('nombre');
        $this->assertSame(1, Persona::count());
        $this->assertSame(0, CuentaAcceso::count());
        DB::statement("CREATE TRIGGER falla_historial BEFORE INSERT ON historial_permisos BEGIN SELECT RAISE(ABORT, 'fallo simulado'); END");
        $this->post('/registro', array_replace($this->registro(), ['nombre' => 'Otro']))->assertServerError();
        $this->assertSame(1, Persona::count());
        $this->assertSame(0, DB::table('usuarios')->count());
        $this->assertSame(0, CuentaAcceso::count());
    }

    public function test_promocion_y_revocacion_preservan_persona_y_lector(): void
    {
        $this->post('/registro', $this->registro());
        $lector = CuentaAcceso::firstOrFail();
        $idLector = $lector->id_usuario;
        $idPersona = $lector->lector->id_persona;
        $admin = $this->cuenta();
        $this->actingAs($admin)->get('/')->assertOk();
        $this->get('/cuentas')->assertOk();
        $this->get('/cuentas/'.$lector->id_cuenta.'/editar')->assertOk()->assertSee('Asignar rol en Editar persona')->assertDontSee('name="rol"', false);
        $this->asignarRol($lector, 'BIBLIOTECARIO')->assertRedirect();
        $lector->refresh();
        $this->assertSame('BIBLIOTECARIO', $lector->rol);
        $this->assertSame($idPersona, $lector->empleado->id_persona);
        $this->assertSame(1, Persona::count());
        $this->put('/cuentas/roles/BIBLIOTECARIO/permisos', ['permisos' => ['libros:crear', 'libros:editar', 'libros:eliminar']])->assertRedirect();
        $libro = DB::table('libros')->insertGetId(['isbn' => 'PRUEBA', 'titulo' => 'Libro prueba']);
        $this->actingAs($lector->fresh())->get('/libros')->assertOk()->assertSee('Editar')->assertSee('Eliminar');
        $this->get('/')->assertOk()->assertSee('Bibliotecario');
        $this->get('/libros/create')->assertOk();
        $this->get('/cuentas')->assertForbidden();
        $this->actingAs($admin);
        $this->asignarRol($lector, 'USUARIO')->assertRedirect();
        $lector->refresh();
        $this->assertSame($idLector, $lector->id_usuario);
        $this->assertFalse($lector->puede('libros', 'crear'));
        $this->actingAs($lector)->get('/libros/create')->assertForbidden();
        $this->delete('/libros/'.$libro)->assertForbidden();
        $this->assertSame(1, DB::table('bibliotecarios')->count());
    }

    public function test_ultimo_admin_protegido_y_historial(): void
    {
        $admin = $this->cuenta();
        $data = ['nombre_usuario' => $admin->nombre_usuario, 'activo' => 0];
        $this->actingAs($admin)->put('/cuentas/'.$admin->id_cuenta, $data)->assertSessionHasErrors('rol');
        $this->assertTrue($admin->fresh()->activo);
        $otro = $this->cuenta();
        $this->put('/cuentas/'.$otro->id_cuenta, ['nombre_usuario' => $otro->nombre_usuario, 'activo' => 1])->assertRedirect();
        $this->get('/cuentas/historial')->assertOk();
        $this->assertSame($admin->id_cuenta, DB::table('historial_permisos')->first()->actor_id);
    }

    public function test_revocacion_de_permisos_y_restricciones_del_lector(): void
    {
        $admin = $this->cuenta();
        $empleado = $this->cuenta('BIBLIOTECARIO');
        $this->actingAs($admin)->put('/cuentas/roles/BIBLIOTECARIO/permisos', ['permisos' => []])->assertRedirect();
        $this->actingAs($empleado)->get('/libros')->assertForbidden();
        $this->get('/libros/create')->assertForbidden();
        $this->actingAs($admin)->put('/cuentas/roles/BIBLIOTECARIO/permisos', ['permisos' => ['libros:crear']])->assertRedirect();
        $this->assertTrue($empleado->fresh()->puede('libros', 'ver'));
        $this->actingAs($empleado->fresh())->get('/libros')->assertOk()->assertSee('Registrar libro');
        $this->actingAs($admin)->put('/cuentas/roles/BIBLIOTECARIO/permisos', ['permisos' => []])->assertRedirect();
        $this->actingAs($empleado->fresh())->get('/libros/create')->assertForbidden();
        $lector = $this->cuenta('USUARIO');
        $this->actingAs($admin)->put('/cuentas/roles/USUARIO/permisos', ['permisos' => ['cuentas:editar']])->assertSessionHasErrors('permisos');
        $this->assertFalse($lector->fresh()->puede('libros', 'eliminar'));
    }

    public function test_bibliotecario_reutiliza_persona_y_no_se_elimina_si_tiene_cuenta(): void
    {
        $admin = $this->cuenta();
        $persona = Persona::create(['nombre' => 'Ana', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura']);
        $this->actingAs($admin)->get('/bibliotecarios/create')->assertOk()->assertSee('Ana');
        $this->post('/bibliotecarios', ['id_persona' => $persona->id_persona])->assertRedirect();
        $this->assertSame(1, Persona::count());
        $empleado = Bibliotecario::firstOrFail();
        $cuenta = $this->cuenta('BIBLIOTECARIO');
        $cuenta->update(['id_bibliotecario' => $empleado->id_bibliotecario]);
        $this->delete('/bibliotecarios/'.$empleado->id_bibliotecario)->assertSessionHas('error');
        $this->assertSame(1, Bibliotecario::count());
    }

    public function test_alta_administrativa_reutiliza_persona_sin_cambiar_nombres(): void
    {
        $persona = Persona::create(['nombre' => 'Persona', 'apellido_paterno' => 'Existente', 'apellido_materno' => 'Autora']);
        DB::table('autores')->insert(['id_persona' => $persona->id_persona]);
        $this->actingAs($this->cuenta())->get('/usuarios/create')->assertOk()->assertSee('Persona Existente Autora');
        $datos = ['id_persona' => $persona->id_persona, 'nombre' => 'Ignorar', 'telefono' => '5511111111', 'id_estado_usuario' => 1];
        $this->post('/usuarios', $datos)->assertRedirect('/usuarios');
        $this->assertSame(1, Persona::count());
        $this->assertSame('Persona', $persona->fresh()->nombre);
        $this->assertSame($persona->id_persona, Usuario::firstOrFail()->id_persona);
        $this->post('/usuarios', array_replace($datos, ['telefono' => '5522222222']))->assertSessionHasErrors('id_persona');
        $this->assertSame(1, DB::table('usuarios')->count());
        $this->assertSame(1, DB::table('autores')->count());
    }

    public function test_crud_libros_persistencia_y_bloqueo_tras_revocacion(): void
    {
        $persona = Persona::create(['nombre' => 'Autor', 'apellido_paterno' => 'De', 'apellido_materno' => 'Prueba']);
        $autor = DB::table('autores')->insertGetId(['id_persona' => $persona->id_persona]);
        $categoria = DB::table('categorias')->insertGetId(['nombre' => 'Pruebas']);
        $editorial = DB::table('editoriales')->insertGetId(['nombre' => 'Editorial']);
        $admin = $this->cuenta();
        $empleado = $this->cuenta('BIBLIOTECARIO');
        $url = '/cuentas/roles/BIBLIOTECARIO/permisos';
        $this->actingAs($admin)->put($url, ['permisos' => ['libros:crear', 'libros:editar', 'libros:eliminar']])->assertRedirect();
        $datos = ['isbn' => 'ISBN-PRUEBA', 'titulo' => 'Libro nuevo', 'autores' => [$autor], 'categorias' => [$categoria], 'editoriales' => [$editorial]];
        $this->actingAs($empleado->fresh())->post('/libros', $datos)->assertRedirect('/libros');
        $libro = Libro::firstOrFail();
        $this->assertSame('Autor De Prueba', $libro->autores->first()->nombre_completo);
        $this->put('/libros/'.$libro->id_libro, array_replace($datos, ['titulo' => 'Libro editado']))->assertRedirect('/libros');
        $this->assertSame('Libro editado', $libro->fresh()->titulo);
        $this->actingAs($admin)->put($url, ['permisos' => ['libros:ver']])->assertRedirect();
        $this->actingAs($empleado->fresh())->get('/libros')->assertOk()->assertDontSee('Registrar libro')->assertDontSee('Editar')->assertDontSee('Eliminar');
        $this->put('/libros/'.$libro->id_libro, $datos)->assertForbidden();
        $this->delete('/libros/'.$libro->id_libro)->assertForbidden();
        $this->actingAs($admin)->put($url, ['permisos' => ['libros:eliminar']])->assertRedirect();
        $this->actingAs($empleado->fresh())->delete('/libros/'.$libro->id_libro)->assertRedirect();
        $this->assertSame(0, Libro::count());
        $this->assertSame(1, DB::table('autores')->count());
    }

    public function test_promocion_superadmin_y_desactivacion_de_sesion_existente(): void
    {
        $this->post('/registro', $this->registro())->assertRedirect('/libros');
        $lector = CuentaAcceso::firstOrFail();
        $admin = $this->cuenta();
        $datos = ['nombre_usuario' => $lector->nombre_usuario, 'activo' => 1];
        $this->actingAs($admin);
        $this->asignarRol($lector, 'SUPERADMIN')->assertRedirect();
        $this->assertSame('SUPERADMIN', $lector->fresh()->rol);
        $this->assertSame(0, $lector->fresh()->permisos()->count());
        $this->actingAs($lector->fresh())->get('/cuentas')->assertOk();
        $this->actingAs($admin)->put('/cuentas/'.$lector->id_cuenta, array_replace($datos, ['activo' => 0]))->assertRedirect('/cuentas');
        $this->actingAs($lector->fresh())->get('/cuentas')->assertForbidden();
        $this->get('/libros')->assertForbidden();
    }

    public function test_migracion_reconoce_esquema_manual_y_rechaza_esquema_parcial(): void
    {
        $migracion = require database_path('migrations/2026_10_20_000001_create_acceso_y_permisos_tables.php');
        $admin = $this->cuenta();
        $migracion->up();
        $this->assertSame($admin->id_cuenta, CuentaAcceso::firstOrFail()->id_cuenta);
        DB::statement('DROP TABLE historial_permisos'); // Solo SQLite :memory: verificado en setUp.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Esquema de acceso parcial');
        $migracion->up();
    }

    public function test_contrasena_larga_se_rechaza_sin_registros_incompletos(): void
    {
        config(['hashing.driver' => 'bcrypt']);
        $clave = str_repeat('á', 37);
        $this->post('/registro', array_replace($this->registro(), ['password' => $clave, 'password_confirmation' => $clave]))->assertSessionHasErrors('password');
        $this->assertSame(0, Persona::count());
        $this->assertSame(0, CuentaAcceso::count());
    }

    public function test_registro_rechaza_solicitudes_sin_token_csrf(): void
    {
        // Activar la comprobación que Laravel omite normalmente durante PHPUnit.
        $this->app['env'] = 'local';
        $this->post('/registro', $this->registro())->assertStatus(419);
        $this->assertSame(0, Persona::count());
        $this->assertSame(0, CuentaAcceso::count());
    }

    public function test_cambio_de_rol_conserva_prestamos_y_relaciones_historicas(): void
    {
        $this->post('/registro', $this->registro())->assertRedirect('/libros');
        $lector = CuentaAcceso::firstOrFail();
        $idLector = $lector->id_usuario;
        $persona = Persona::create(['nombre' => 'Empleado', 'apellido_paterno' => 'Con', 'apellido_materno' => 'Historia']);
        $empleado = DB::table('bibliotecarios')->insertGetId(['id_persona' => $persona->id_persona]);
        $prestamo = DB::table('prestamos')->insertGetId(['id_usuario' => $idLector, 'id_bibliotecario' => $empleado, 'fecha_prestamo' => '2026-10-01', 'fecha_limite' => '2026-10-15']);
        $libro = DB::table('libros')->insertGetId(['isbn' => 'HISTORICO', 'titulo' => 'Libro histórico']);
        $ubicacion = DB::table('ubicaciones_libros')->insertGetId(['nombre_ubicacion' => 'Estante']);
        $estado = DB::table('estados_ejemplar')->insertGetId(['nombre_estado' => 'DISPONIBLE']);
        $ejemplar = DB::table('ejemplares')->insertGetId(['codigo_inventario' => 'H-1', 'id_libro' => $libro, 'id_ubicacion' => $ubicacion, 'id_estado_ejemplar' => $estado]);
        $detalle = DB::table('detalle_prestamo')->insertGetId(['id_prestamo' => $prestamo, 'id_ejemplar' => $ejemplar]);
        $devolucion = DB::table('devoluciones')->insertGetId(['id_prestamo' => $prestamo, 'fecha_devolucion' => '2026-10-10']);
        DB::table('detalle_devolucion')->insert(['id_devolucion' => $devolucion, 'id_detalle_prestamo' => $detalle]);
        $tablas = ['personas', 'usuarios', 'libros', 'ejemplares', 'prestamos', 'detalle_prestamo', 'devoluciones', 'detalle_devolucion'];
        $antes = [];
        foreach ($tablas as $tabla) {
            $antes[$tabla] = DB::table($tabla)->get()->toJson();
        }
        $this->actingAs($this->cuenta());
        foreach (['BIBLIOTECARIO', 'SUPERADMIN', 'USUARIO'] as $rol) {
            $this->put('/personas/'.$lector->lector->id_persona, [
                'nombre' => 'Lector', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Seguro',
                'acceso' => ['gestionar' => 1, 'cuenta_id' => $lector->id_cuenta, 'activo' => 1, 'rol' => $rol],
            ])->assertRedirect();
        }
        foreach ($antes as $tabla => $filas) {
            $this->assertSame($filas, DB::table($tabla)->get()->toJson(), $tabla);
        }
        $this->assertSame($idLector, $lector->fresh()->id_usuario);
        $this->delete('/usuarios/'.$idLector)->assertSessionHas('error');
        $this->delete('/libros/'.$libro)->assertSessionHas('error');
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }

    public function test_editar_persona_crea_cuentas_con_credenciales_y_login_para_cada_rol(): void
    {
        $admin = $this->cuenta();
        foreach (['USUARIO' => '/libros', 'BIBLIOTECARIO' => '/', 'SUPERADMIN' => '/'] as $rol => $destino) {
            $persona = Persona::create(['nombre' => $rol, 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Acceso']);
            $this->actingAs($admin)->get('/personas/'.$persona->id_persona.'/edit')->assertOk()->assertSee('Asignación de rol')->assertSee('Crear cuenta con credenciales');
            $clave = '  UnaClaveConEspacios123!  ';
            $datos = ['nombre' => $persona->nombre, 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Acceso', 'acceso' => [
                'gestionar' => 1, 'rol' => $rol, 'activo' => 1, 'telefono' => '5511111111',
                'nombre_usuario' => strtolower($rol).'.prueba', 'password' => $clave, 'password_confirmation' => $clave,
            ]];
            $this->put('/personas/'.$persona->id_persona, $datos)->assertRedirect('/personas/'.$persona->id_persona.'/edit');
            $cuenta = CuentaAcceso::dePersona($persona->id_persona)->sole();
            $this->assertSame($rol, $cuenta->rol);
            $this->assertTrue(Hash::check($clave, $cuenta->password));
            $this->get('/personas/'.$persona->id_persona.'/edit')->assertOk()->assertSee($cuenta->nombre_usuario);
            $historial = DB::table('historial_permisos')->where('cuenta_id', $cuenta->id_cuenta)->first();
            $detalle = json_decode($historial->detalle, true);
            $this->assertSame($admin->id_cuenta, $historial->actor_id);
            $this->assertNull($detalle['rol_anterior']);
            $this->assertSame($rol, $detalle['rol_nuevo']);
            $this->assertNotNull($historial->created_at);
            $this->post('/logout');
            $this->post('/login', ['nombre_usuario' => $cuenta->nombre_usuario, 'password' => $clave])->assertRedirect($destino);
            $this->assertAuthenticatedAs($cuenta);
            $this->get('/libros')->assertOk();
            if ($rol !== 'SUPERADMIN') {
                $this->get('/cuentas')->assertForbidden();
                $this->assertFalse($cuenta->fresh()->puede('libros', 'eliminar'));
                if ($rol === 'USUARIO') {
                    $this->post('/libros', [])->assertForbidden();
                }
            }
            $this->post('/logout');
        }
        $this->assertSame(3, Persona::count());
    }

    public function test_editar_persona_promueve_revoca_y_login_refleja_nuevo_rol(): void
    {
        $this->post('/registro', $this->registro());
        $cuenta = CuentaAcceso::firstOrFail();
        $persona = $cuenta->lector->persona;
        $admin = $this->cuenta();
        $datos = $persona->only(['nombre', 'apellido_paterno', 'apellido_materno']);
        foreach (['BIBLIOTECARIO' => '/', 'SUPERADMIN' => '/', 'USUARIO' => '/libros'] as $rol => $destino) {
            $anterior = $cuenta->fresh()->rol;
            $this->actingAs($admin)->put('/personas/'.$persona->id_persona, $datos + ['acceso' => ['gestionar' => 1, 'cuenta_id' => $cuenta->id_cuenta, 'rol' => $rol, 'activo' => 1]])->assertRedirect();
            $this->assertSame($rol, $cuenta->fresh()->rol);
            $this->get('/personas/'.$persona->id_persona.'/edit')->assertOk()->assertSee('value="'.$rol.'" selected', false);
            if ($rol === 'BIBLIOTECARIO') {
                $cuenta->permisos()->create(['modulo' => 'libros', 'accion' => 'crear']);
                $this->assertTrue($cuenta->fresh()->puede('libros', 'crear'));
            }
            $cambio = DB::table('historial_permisos')->orderByDesc('id_cambio')->first();
            $detalle = json_decode($cambio->detalle, true);
            $this->assertSame($admin->id_cuenta, $cambio->actor_id);
            $this->assertSame($anterior, $detalle['rol_anterior']);
            $this->assertSame($rol, $detalle['rol_nuevo']);
            $this->post('/logout');
            $this->post('/login', ['nombre_usuario' => $cuenta->nombre_usuario, 'password' => 'UnaClaveSegura123!'])->assertRedirect($destino);
            $this->get('/libros')->assertOk();
            if ($rol !== 'SUPERADMIN') {
                $this->get('/cuentas')->assertForbidden();
            }
            $this->post('/logout');
        }
        $this->assertSame(1, Persona::count());
        $this->assertSame(1, Bibliotecario::count());
        $this->assertSame(1, Usuario::count());
        $this->assertFalse($cuenta->fresh()->puede('libros', 'crear'));
    }

    public function test_personal_edita_datos_pero_no_ve_ni_manipula_acceso(): void
    {
        $persona = Persona::create(['nombre' => 'Original', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura']);
        $empleado = $this->cuenta('BIBLIOTECARIO');
        foreach (['ver', 'editar'] as $accion) {
            $empleado->permisos()->create(['modulo' => 'personas', 'accion' => $accion]);
        }
        $empleado->permisos()->create(['modulo' => 'usuarios', 'accion' => 'ver']);
        $this->actingAs($empleado)->get('/personas/'.$persona->id_persona.'/edit')->assertOk()->assertDontSee('Asignación de rol')->assertDontSee('acceso[', false);
        $this->get('/personas')->assertOk();
        $this->get('/usuarios')->assertOk();
        $this->put('/cuentas/roles/BIBLIOTECARIO/permisos', ['permisos' => ['libros:eliminar']])->assertForbidden();
        $datos = ['nombre' => 'Editado', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura'];
        $this->put('/personas/'.$persona->id_persona, $datos)->assertRedirect();
        $this->assertSame('Editado', $persona->fresh()->nombre);
        foreach ([['acceso' => ['gestionar' => 1, 'rol' => 'SUPERADMIN']], ['rol' => 'SUPERADMIN'], ['acceso' => ['gestionar' => 0]]] as $ataque) {
            $this->put('/personas/'.$persona->id_persona, array_replace($datos, ['nombre' => 'Ataque']) + $ataque)->assertForbidden();
        }
        $this->assertSame('Editado', $persona->fresh()->nombre);
        $this->assertSame('BIBLIOTECARIO', $empleado->fresh()->rol);
        $this->actingAs($this->cuenta('USUARIO'))->get('/personas/'.$persona->id_persona.'/edit')->assertForbidden();
        $this->put('/personas/'.$persona->id_persona, $datos + ['acceso' => ['rol' => 'SUPERADMIN']])->assertForbidden();
    }

    public function test_persona_no_modifica_cuenta_ajena_y_protege_ultimo_admin(): void
    {
        $persona = Persona::create(['nombre' => 'Admin', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura']);
        $empleado = Bibliotecario::create(['id_persona' => $persona->id_persona]);
        $admin = $this->cuenta();
        $admin->update(['id_bibliotecario' => $empleado->id_bibliotecario]);
        $otra = Persona::create(['nombre' => 'Otra', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura']);
        $datos = ['nombre' => 'No guardar', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura'];
        $this->actingAs($admin)->get('/personas/'.$otra->id_persona.'/edit?cuenta='.$admin->id_cuenta)->assertNotFound();
        $this->put('/personas/'.$otra->id_persona, $datos + ['acceso' => ['gestionar' => 1, 'cuenta_id' => $admin->id_cuenta, 'rol' => 'USUARIO', 'activo' => 1]])->assertNotFound();
        foreach ([['rol' => 'SUPERADMIN', 'activo' => 0], ['rol' => 'BIBLIOTECARIO', 'activo' => 1]] as $cambio) {
            $this->put('/personas/'.$persona->id_persona, $datos + ['acceso' => ['gestionar' => 1, 'cuenta_id' => $admin->id_cuenta] + $cambio])->assertSessionHasErrors('rol');
        }
        $this->assertSame('Admin', $persona->fresh()->nombre);
        $this->assertSame('Otra', $otra->fresh()->nombre);
        $this->assertSame('SUPERADMIN', $admin->fresh()->rol);
        $this->assertTrue($admin->fresh()->activo);
        $this->assertSame(0, DB::table('historial_permisos')->count());
    }

    public function test_persona_sin_cuenta_exige_credenciales_y_revierte_si_falla_historial(): void
    {
        $persona = Persona::create(['nombre' => 'Original', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura']);
        $this->actingAs($this->cuenta());
        $datos = ['nombre' => 'No guardar', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura', 'acceso' => ['gestionar' => 1, 'rol' => 'BIBLIOTECARIO', 'activo' => 1, 'nombre_usuario' => 'nueva.cuenta']];
        $this->put('/personas/'.$persona->id_persona, $datos)->assertSessionHasErrors('acceso.password');
        $datos['acceso'] += ['password' => 'UnaClaveSegura123!', 'password_confirmation' => 'Distinta'];
        $this->put('/personas/'.$persona->id_persona, $datos)->assertSessionHasErrors('acceso.password');
        $this->assertNull(session()->getOldInput('acceso.password'));
        $this->assertNull(session()->getOldInput('acceso.password_confirmation'));
        $datos['acceso']['password_confirmation'] = $datos['acceso']['password'];
        DB::statement("CREATE TRIGGER falla_historial BEFORE INSERT ON historial_permisos BEGIN SELECT RAISE(ABORT, 'fallo simulado'); END");
        $this->put('/personas/'.$persona->id_persona, $datos)->assertServerError();
        $this->assertSame('Original', $persona->fresh()->nombre);
        $this->assertSame(1, CuentaAcceso::count());
        $this->assertSame(0, Bibliotecario::count());
    }

    public function test_persona_activa_cuenta_sin_cambiar_su_clave_ni_permisos(): void
    {
        $this->post('/registro', $this->registro());
        $lector = CuentaAcceso::firstOrFail();
        $lector->update(['activo' => false]);
        $persona = $lector->lector->persona;
        $hash = $lector->password;
        $this->actingAs($this->cuenta())->put('/personas/'.$persona->id_persona, $persona->only(['nombre', 'apellido_paterno', 'apellido_materno']) + ['acceso' => ['gestionar' => 1, 'cuenta_id' => $lector->id_cuenta, 'rol' => 'USUARIO', 'activo' => 1]])->assertRedirect();
        $this->assertTrue($lector->fresh()->activo);
        $this->assertSame($hash, $lector->fresh()->password);
        $this->assertSame(0, $lector->permisos()->count());
        $this->post('/logout');
        $this->post('/login', ['nombre_usuario' => $lector->nombre_usuario, 'password' => 'UnaClaveSegura123!'])->assertRedirect('/libros');
    }

    public function test_persona_con_varias_cuentas_modifica_solo_la_seleccionada(): void
    {
        $this->post('/registro', $this->registro());
        $lector = CuentaAcceso::firstOrFail();
        $persona = $lector->lector->persona;
        $empleado = Bibliotecario::create(['id_persona' => $persona->id_persona]);
        $personal = $this->cuenta('BIBLIOTECARIO');
        $personal->update(['id_bibliotecario' => $empleado->id_bibliotecario]);
        $this->actingAs($this->cuenta())->get('/personas/'.$persona->id_persona.'/edit?cuenta='.$personal->id_cuenta)->assertOk()->assertSee('Esta persona tiene varias cuentas')->assertSee('value="'.$personal->id_cuenta.'"', false);
        $datos = $persona->only(['nombre', 'apellido_paterno', 'apellido_materno']);
        $this->put('/personas/'.$persona->id_persona, $datos + ['acceso' => ['gestionar' => 1, 'cuenta_id' => $personal->id_cuenta, 'rol' => 'BIBLIOTECARIO', 'activo' => 0]])->assertRedirect();
        $this->assertFalse($personal->fresh()->activo);
        $this->assertTrue($lector->fresh()->activo);
        $this->assertSame('USUARIO', $lector->fresh()->rol);
        $this->put('/personas/'.$persona->id_persona, $datos + ['acceso' => ['gestionar' => 1, 'cuenta_id' => $personal->id_cuenta, 'rol' => 'USUARIO', 'activo' => 1]])->assertSessionHasErrors('rol');
        $this->assertSame('BIBLIOTECARIO', $personal->fresh()->rol);
        $this->assertFalse($personal->fresh()->activo);
    }

    public function test_editar_solo_datos_no_crea_cuenta_y_activacion_exige_hash_valido(): void
    {
        $persona = Persona::create(['nombre' => 'Original', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura']);
        $admin = $this->cuenta();
        $datos = ['nombre' => 'Editado', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura'];
        $this->actingAs($admin)->put('/personas/'.$persona->id_persona, $datos)->assertRedirect();
        $this->assertSame('Editado', $persona->fresh()->nombre);
        $this->assertFalse(CuentaAcceso::dePersona($persona->id_persona)->exists());
        $empleado = Bibliotecario::create(['id_persona' => $persona->id_persona]);
        $cuenta = $this->cuenta('BIBLIOTECARIO');
        $cuenta->update(['id_bibliotecario' => $empleado->id_bibliotecario, 'activo' => false]);
        DB::table('cuentas_acceso')->where('id_cuenta', $cuenta->id_cuenta)->update(['password' => 'clave-legacy-sin-hash']);
        $this->put('/personas/'.$persona->id_persona, array_replace($datos, ['nombre' => 'No guardar']) + ['acceso' => ['gestionar' => 1, 'cuenta_id' => $cuenta->id_cuenta, 'rol' => 'BIBLIOTECARIO', 'activo' => 1]])->assertSessionHasErrors('activo');
        $this->assertFalse($cuenta->fresh()->activo);
        $this->assertSame('Editado', $persona->fresh()->nombre);
    }

    public function test_cuentas_no_asigna_roles_ni_crea_cuentas_incluso_para_superadmin(): void
    {
        $this->post('/registro', $this->registro());
        $lector = CuentaAcceso::firstOrFail();
        $admin = $this->cuenta();
        $datos = ['nombre_usuario' => $lector->nombre_usuario, 'activo' => 1];
        $historial = DB::table('historial_permisos')->count();
        $this->actingAs($admin)->get('/cuentas/nueva')->assertRedirect('/personas');
        $this->get('/cuentas')->assertOk()->assertSee('Asignar roles desde Personas')->assertDontSee('+ Crear cuenta');
        $this->get('/cuentas/'.$lector->id_cuenta.'/editar')->assertOk()->assertDontSee('name="rol"', false)->assertDontSee('id="rolEditar"', false);
        foreach (['BIBLIOTECARIO', 'SUPERADMIN', 'USUARIO'] as $rol) {
            $this->put('/cuentas/'.$lector->id_cuenta, $datos + ['rol' => $rol])->assertForbidden();
        }
        $this->put('/cuentas/'.$lector->id_cuenta, $datos + ['acceso' => ['rol' => 'SUPERADMIN']])->assertForbidden();
        $this->post('/cuentas', ['nombre_usuario' => 'cuenta.duplicada', 'password' => 'UnaClaveSegura123!', 'password_confirmation' => 'UnaClaveSegura123!', 'rol' => 'BIBLIOTECARIO', 'id_usuario' => $lector->id_usuario])->assertForbidden();
        $this->assertSame('USUARIO', $lector->fresh()->rol);
        $this->assertSame(2, CuentaAcceso::count());
        $this->assertSame($historial, DB::table('historial_permisos')->count());
        $this->put('/cuentas/'.$lector->id_cuenta, ['nombre_usuario' => 'lector.renombrado', 'activo' => 1, 'password' => 'NuevaClaveSegura123!', 'password_confirmation' => 'NuevaClaveSegura123!'])->assertRedirect('/cuentas');
        $this->assertSame('USUARIO', $lector->fresh()->rol);
        $this->assertSame(0, $lector->permisos()->count());
        $this->assertTrue(Hash::check('NuevaClaveSegura123!', $lector->fresh()->password));
        $this->post('/logout');
        $this->post('/login', ['nombre_usuario' => 'lector.renombrado', 'password' => 'NuevaClaveSegura123!'])->assertRedirect('/libros');
    }

    public function test_listado_personas_distingue_relaciones_y_rol_real_de_acceso(): void
    {
        $persona = Persona::create(['nombre' => 'PersonaSinCuenta', 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura']);
        Bibliotecario::create(['id_persona' => $persona->id_persona]);
        $admin = $this->cuenta();
        $this->actingAs($admin)->get('/personas?buscar=PersonaSinCuenta')->assertOk()->assertSee('PERSONAL BIBLIOTECARIO')->assertSee('Rol de acceso')->assertSee('SIN CUENTA');
        $datos = $persona->only(['nombre', 'apellido_paterno', 'apellido_materno']);
        $this->put('/personas/'.$persona->id_persona, $datos + ['acceso' => ['gestionar' => 1, 'rol' => 'SUPERADMIN', 'activo' => 1, 'nombre_usuario' => 'persona.concuenta', 'password' => 'UnaClaveSegura123!', 'password_confirmation' => 'UnaClaveSegura123!']])->assertRedirect();
        $this->get('/personas?buscar=PersonaSinCuenta')->assertOk()->assertSee('persona.concuenta')->assertDontSee('SIN CUENTA');
        $this->assertSame('SUPERADMIN', CuentaAcceso::dePersona($persona->id_persona)->sole()->rol);
        $this->assertSame(1, Bibliotecario::count());
        $empleado = $this->cuenta('BIBLIOTECARIO');
        $empleado->permisos()->create(['modulo' => 'personas', 'accion' => 'ver']);
        $this->actingAs($empleado)->get('/personas?buscar=PersonaSinCuenta')->assertOk()->assertDontSee('Rol de acceso')->assertDontSee('persona.concuenta');
    }
}
