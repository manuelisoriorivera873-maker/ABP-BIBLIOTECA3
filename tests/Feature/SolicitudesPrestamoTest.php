<?php

namespace Tests\Feature;

use App\Models\Bibliotecario;
use App\Models\CuentaAcceso;
use App\Models\Ejemplar;
use App\Models\Persona;
use App\Models\Prestamo;
use App\Models\SolicitudPrestamo;
use App\Models\Usuario;
use App\Services\SesionesOperativas;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SolicitudesPrestamoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        DB::unprepared(file_get_contents(__DIR__.'/../Fixtures/schema.sql'));
        foreach (['2026_10_22_000002_create_permisos_por_rol.php', '2026_10_22_000003_create_solicitudes_prestamo.php'] as $archivo) {
            (require database_path('migrations/'.$archivo))->up();
        }
        DB::table('estados_usuario')->insert([
            ['id_estado_usuario' => 1, 'nombre_estado_usuario' => 'ACTIVO'],
            ['id_estado_usuario' => 2, 'nombre_estado_usuario' => 'SUSPENDIDO'],
        ]);
        DB::table('estados_ejemplar')->insert([
            ['id_estado_ejemplar' => 1, 'nombre_estado' => 'DISPONIBLE'],
            ['id_estado_ejemplar' => 2, 'nombre_estado' => 'PRESTADO'],
        ]);
        DB::table('ubicaciones_libros')->insert(['id_ubicacion' => 1, 'nombre_ubicacion' => 'Estante']);
        for ($libro = 1; $libro <= 4; $libro++) {
            DB::table('libros')->insert(['id_libro' => $libro, 'titulo' => 'Título '.$libro, 'isbn' => 'isbn-'.$libro]);
            for ($copia = 1; $copia <= 4; $copia++) {
                Ejemplar::create(['codigo_inventario' => "$libro-$copia", 'id_libro' => $libro, 'id_ubicacion' => 1, 'id_estado_ejemplar' => 1]);
            }
        }
    }

    private function cuenta(string $rol = 'USUARIO'): CuentaAcceso
    {
        $persona = Persona::create(['nombre' => 'Persona '.Persona::count(), 'apellido_paterno' => 'Prueba', 'apellido_materno' => 'Segura']);
        $relacion = $rol === 'USUARIO'
            ? ['id_usuario' => Usuario::create(['id_persona' => $persona->getKey(), 'telefono' => '55'.str_pad((string) $persona->getKey(), 8, '0', STR_PAD_LEFT), 'id_estado_usuario' => 1])->getKey()]
            : ['id_bibliotecario' => Bibliotecario::create(['id_persona' => $persona->getKey()])->getKey()];

        return CuentaAcceso::create($relacion + ['nombre_usuario' => 'cuenta'.CuentaAcceso::count(), 'password' => 'UnaClaveSegura123!', 'rol' => $rol, 'activo' => true]);
    }

    /** Dos navegadores HTTP independientes usando el almacenamiento real de sesión de prueba. */
    private function navegar(?string &$id, string $metodo, string $url, array $datos = [])
    {
        $sesion = app('session')->driver();
        $sesion->flush();
        $sesion->setId($id ?? Str::random(40));
        $sesion->start();
        Auth::forgetGuards();
        $this->withCookie(config('session.cookie'), $sesion->getId());
        $respuesta = $this->{strtolower($metodo)}($url, $datos);
        $id = $sesion->getId();

        return $respuesta;
    }

    private function login(?string &$sesion, CuentaAcceso $cuenta): void
    {
        $this->navegar($sesion, 'POST', '/login', ['nombre_usuario' => $cuenta->nombre_usuario, 'password' => 'UnaClaveSegura123!'])
            ->assertRedirect($cuenta->rol === 'USUARIO' ? '/libros' : '/');
        $this->assertAuthenticatedAs($cuenta);
    }

    public function test_login_envio_multititulo_asignacion_aprobacion_entrega_y_consulta_del_lector(): void
    {
        $lector = $this->cuenta();
        $personal = $this->cuenta('BIBLIOTECARIO');
        $sesionLector = $sesionPersonal = null;
        $this->login($sesionPersonal, $personal);
        $this->navegar($sesionPersonal, 'GET', '/')->assertOk()->assertSee('Solicitudes de préstamo');
        $this->navegar($sesionPersonal, 'POST', '/solicitudes/disponibilidad', ['disponible' => 1])->assertRedirect('/solicitudes');
        $this->navegar($sesionPersonal, 'GET', '/solicitudes')->assertOk()->assertSee('disponible');
        $this->login($sesionLector, $lector);
        $this->navegar($sesionLector, 'GET', '/libros')->assertOk()->assertSee('Catálogo de libros')->assertSee('Solicitar préstamo')->assertSee('Mis solicitudes')->assertSee('Mis préstamos');
        $this->navegar($sesionLector, 'GET', '/solicitudes/nueva')->assertOk()->assertSee('Título 1')->assertSee('Resumen de la solicitud');
        $this->navegar($sesionLector, 'POST', '/solicitudes', ['libros' => [1 => 3, 2 => 2], 'id_usuario' => 999])->assertRedirect('/solicitudes')->assertSessionHasNoErrors();
        $solicitud = SolicitudPrestamo::firstOrFail();
        $this->assertEquals($lector->id_usuario, $solicitud->id_usuario);
        $this->assertEquals($personal->getKey(), $solicitud->asignada_a);
        $this->assertSame(5, $solicitud->detalles->sum('cantidad'));
        $this->assertSame(0, Prestamo::count());
        $this->navegar($sesionPersonal, 'GET', '/solicitudes')->assertOk()->assertSee('Título 1')->assertSee('Aprobar');
        $this->navegar($sesionPersonal, 'PUT', '/solicitudes/'.$solicitud->getKey(), ['decision' => 'aprobar'])->assertRedirect('/solicitudes')->assertSessionHasNoErrors();
        $this->assertSame('APROBADA', $solicitud->fresh()->estado);
        $this->assertSame(0, Prestamo::count());
        $this->navegar($sesionLector, 'GET', '/solicitudes')->assertOk()->assertSee('APROBADA')->assertDontSee('Confirmar entrega');
        $this->navegar($sesionPersonal, 'PUT', '/solicitudes/'.$solicitud->getKey(), ['decision' => 'entregar', 'fecha_limite' => today()->addDays(7)->toDateString()])->assertRedirect('/solicitudes')->assertSessionHasNoErrors();
        $this->assertSame('ENTREGADA', $solicitud->fresh()->estado);
        $this->assertSame(1, Prestamo::count());
        $this->assertSame(5, Prestamo::first()->detalles()->count());
        $this->assertSame(5, Ejemplar::where('id_estado_ejemplar', 2)->count());
        $this->navegar($sesionLector, 'GET', '/solicitudes')->assertOk()->assertSee('ENTREGADA');
        $this->navegar($sesionLector, 'GET', '/lector/prestamos')->assertOk()->assertSee('Mis préstamos')->assertSee('Título 1')->assertViewHas('prestamos', fn ($p) => $p->total() === 1);
        $this->navegar($sesionPersonal, 'PUT', '/solicitudes/'.$solicitud->getKey(), ['decision' => 'entregar', 'fecha_limite' => today()->toDateString()])->assertStatus(409);
        $this->assertSame(1, Prestamo::count());
    }

    public function test_pendiente_sin_personal_recepcion_posterior_y_rechazo_visible_solo_al_dueno(): void
    {
        $lector = $this->cuenta();
        $otro = $this->cuenta();
        $personal = $this->cuenta('BIBLIOTECARIO');
        $this->actingAs($lector)->post('/solicitudes', ['libros' => [1 => 1]])->assertRedirect('/solicitudes');
        $solicitud = SolicitudPrestamo::firstOrFail();
        $this->assertNull($solicitud->asignada_a);
        $this->actingAs($otro)->get('/solicitudes')->assertOk()->assertViewHas('solicitudes', fn ($p) => $p->total() === 0);
        $sesion = null;
        $this->login($sesion, $personal);
        $this->navegar($sesion, 'POST', '/solicitudes/disponibilidad', ['disponible' => 1])->assertRedirect('/solicitudes');
        $this->navegar($sesion, 'GET', '/solicitudes')->assertOk();
        $this->assertEquals($personal->getKey(), $solicitud->fresh()->asignada_a);
        $this->navegar($sesion, 'PUT', '/solicitudes/'.$solicitud->getKey(), ['decision' => 'rechazar'])->assertSessionHasErrors('motivo');
        $this->navegar($sesion, 'PUT', '/solicitudes/'.$solicitud->getKey(), ['decision' => 'rechazar', 'motivo' => 'Ejemplar pendiente de reparación'])->assertRedirect('/solicitudes');
        $this->actingAs($lector)->get('/solicitudes')->assertOk()->assertSee('RECHAZADA')->assertSee('Ejemplar pendiente de reparación');
        $this->actingAs($otro)->get('/solicitudes')->assertOk()->assertDontSee('Ejemplar pendiente de reparación');
        $this->assertSame(0, Prestamo::count());
    }

    public function test_limites_cantidades_estado_disponibilidad_y_duplicacion_de_solicitudes(): void
    {
        $lector = $this->cuenta();
        $this->actingAs($lector);
        foreach ([[], [1 => 0], [1 => -1], [1 => 'a'], [1 => 4], [1 => 3, 2 => 3]] as $libros) {
            $this->post('/solicitudes', ['libros' => $libros])->assertSessionHasErrors();
            $this->assertSame(0, SolicitudPrestamo::count());
        }
        $this->postJson('/solicitudes', ['libros' => [999 => 1]])->assertStatus(422);
        Ejemplar::where('id_libro', 1)->update(['id_estado_ejemplar' => 2]);
        $this->post('/solicitudes', ['libros' => [1 => 1]])->assertSessionHasErrors('libros');
        $lector->lector->update(['id_estado_usuario' => 2]);
        $this->post('/solicitudes', ['libros' => [2 => 1]])->assertSessionHasErrors('libros');
        $lector->lector->update(['id_estado_usuario' => 1]);
        $this->post('/solicitudes', ['libros' => [2 => 3, 3 => 2]])->assertRedirect('/solicitudes')->assertSessionHasNoErrors();
        $this->post('/solicitudes', ['libros' => [4 => 1]])->assertSessionHasErrors('libros');
        $this->assertSame(1, SolicitudPrestamo::count());
    }

    public function test_prestamos_activos_y_vencidos_se_validan_sin_confiar_solo_en_estado_del_ejemplar(): void
    {
        $lector = $this->cuenta();
        $personal = $this->cuenta('BIBLIOTECARIO');
        $prestamo = Prestamo::create(['id_usuario' => $lector->id_usuario, 'id_bibliotecario' => $personal->id_bibliotecario, 'fecha_prestamo' => today()->subDays(2), 'fecha_limite' => today()->subDay()]);
        $prestamo->detalles()->create(['id_ejemplar' => 1]);
        $this->actingAs($lector)->post('/solicitudes', ['libros' => [2 => 1]])->assertSessionHasErrors('libros');
        $prestamo->update(['fecha_limite' => today()->addDay()]);
        $this->post('/solicitudes', ['libros' => [1 => 3]])->assertSessionHasErrors('libros');
        $otro = $this->cuenta();
        $this->actingAs($otro)->get('/solicitudes/nueva')->assertOk()->assertViewHas('libros', fn ($libros) => $libros->find(1)->disponibles === 3);
        $this->get('/lector/prestamos')->assertOk()->assertViewHas('prestamos', fn ($p) => $p->total() === 0);
    }

    public function test_autorizacion_de_servidor_revocacion_inmediata_y_matriz_centralizada(): void
    {
        $lector = $this->cuenta();
        $otro = $this->cuenta();
        $personal = $this->cuenta('BIBLIOTECARIO');
        $personal2 = $this->cuenta('BIBLIOTECARIO');
        $this->get('/solicitudes/nueva')->assertRedirect('/login');
        $this->actingAs($lector)->post('/solicitudes', ['libros' => [1 => 1]])->assertRedirect('/solicitudes');
        $id = SolicitudPrestamo::first()->getKey();
        DB::table('rol_permisos')->insert(['rol' => 'USUARIO', 'modulo' => 'solicitudes', 'accion' => 'editar']);
        $this->put('/solicitudes/'.$id, ['decision' => 'aprobar'])->assertForbidden();
        $this->post('/solicitudes/disponibilidad', ['disponible' => 1])->assertForbidden();
        $this->actingAs($personal)->post('/solicitudes', ['libros' => [1 => 1]])->assertForbidden();
        $this->get('/cuentas')->assertForbidden();
        DB::table('rol_permisos')->where(['rol' => 'USUARIO', 'modulo' => 'solicitudes', 'accion' => 'crear'])->delete();
        foreach ([$lector, $otro] as $cuenta) {
            $this->actingAs($cuenta)->get('/solicitudes/nueva')->assertForbidden();
            $this->post('/solicitudes', ['libros' => [1 => 1]])->assertForbidden();
        }
        DB::table('rol_permisos')->where(['rol' => 'BIBLIOTECARIO', 'modulo' => 'solicitudes', 'accion' => 'editar'])->delete();
        foreach ([$personal, $personal2] as $cuenta) {
            $this->actingAs($cuenta)->put('/solicitudes/'.$id, ['decision' => 'aprobar'])->assertForbidden();
        }
        $this->actingAs($this->cuenta('SUPERADMIN'))->get('/solicitudes')->assertOk();
        $this->put('/solicitudes/'.$id, ['decision' => 'aprobar'])->assertRedirect('/solicitudes');
        $this->assertSame(0, DB::table('cuenta_permisos')->count());
    }

    public function test_sesion_cerrada_expirada_o_no_disponible_no_recibe_solicitudes(): void
    {
        $personal = $this->cuenta('BIBLIOTECARIO');
        $sesion = null;
        $this->login($sesion, $personal);
        $this->navegar($sesion, 'GET', '/solicitudes')->assertOk();
        $this->assertSame([], app(SesionesOperativas::class)->disponibles());
        $this->navegar($sesion, 'POST', '/solicitudes/disponibilidad', ['disponible' => 1])->assertRedirect('/solicitudes');
        $this->assertArrayHasKey($personal->getKey(), app(SesionesOperativas::class)->disponibles());
        $this->travel(6)->minutes();
        $this->assertSame([], app(SesionesOperativas::class)->disponibles());
        $this->travelBack();
        $this->navegar($sesion, 'POST', '/logout')->assertRedirect('/login');
        $this->assertSame([], app(SesionesOperativas::class)->disponibles());
        $this->actingAs($this->cuenta())->post('/solicitudes', ['libros' => [1 => 1]])->assertRedirect('/solicitudes');
        $this->assertNull(SolicitudPrestamo::first()->asignada_a);
    }

    public function test_revalidacion_al_entregar_y_rollback_sin_prestamos_parciales(): void
    {
        $lector = $this->cuenta();
        $personal = $this->cuenta('BIBLIOTECARIO');
        $this->actingAs($lector)->post('/solicitudes', ['libros' => [1 => 2, 2 => 1]])->assertRedirect('/solicitudes');
        $solicitud = SolicitudPrestamo::first();
        $url = '/solicitudes/'.$solicitud->getKey();
        $this->actingAs($personal)->put($url, ['decision' => 'entregar', 'fecha_limite' => today()->toDateString()])->assertStatus(409);
        $this->put($url, ['decision' => 'aprobar'])->assertRedirect('/solicitudes');
        Ejemplar::where('id_libro', 1)->update(['id_estado_ejemplar' => 2]);
        $this->put($url, ['decision' => 'entregar', 'fecha_limite' => today()->toDateString()])->assertSessionHasErrors('libros');
        $this->assertSame(0, Prestamo::count());
        $this->assertSame('APROBADA', $solicitud->fresh()->estado);
        Ejemplar::where('id_libro', 1)->update(['id_estado_ejemplar' => 1]);
        DB::unprepared("CREATE TRIGGER fallo_entrega BEFORE INSERT ON detalle_prestamo BEGIN SELECT RAISE(ABORT, 'fallo deliberado'); END");
        $this->put($url, ['decision' => 'entregar', 'fecha_limite' => today()->toDateString()])->assertStatus(500);
        $this->assertSame(0, Prestamo::count());
        $this->assertSame(0, DB::table('detalle_prestamo')->count());
        $this->assertSame(0, Ejemplar::where('id_estado_ejemplar', 2)->count());
        $this->assertSame('APROBADA', $solicitud->fresh()->estado);
    }

    public function test_fallo_al_guardar_detalles_revierte_toda_la_solicitud(): void
    {
        DB::unprepared("CREATE TRIGGER fallo_solicitud BEFORE INSERT ON solicitud_detalles BEGIN SELECT RAISE(ABORT, 'fallo deliberado'); END");
        $this->actingAs($this->cuenta())->post('/solicitudes', ['libros' => [1 => 1]])->assertStatus(500);
        $this->assertSame(0, SolicitudPrestamo::count());
        $this->assertSame(0, DB::table('solicitud_detalles')->count());
    }

    public function test_asignacion_respeta_responsable_activo_y_descarta_permisos_revocados(): void
    {
        $a = $this->cuenta('BIBLIOTECARIO');
        $b = $this->cuenta('BIBLIOTECARIO');
        $lector = $this->cuenta();
        $sesionA = $sesionB = null;
        $this->login($sesionA, $a);
        $this->navegar($sesionA, 'POST', '/solicitudes/disponibilidad', ['disponible' => 1])->assertRedirect('/solicitudes');
        $this->login($sesionB, $b);
        $this->navegar($sesionB, 'POST', '/solicitudes/disponibilidad', ['disponible' => 1])->assertRedirect('/solicitudes');
        $this->actingAs($lector)->post('/solicitudes', ['libros' => [1 => 1]])->assertRedirect('/solicitudes');
        $solicitud = SolicitudPrestamo::firstOrFail();
        $this->assertEquals($a->getKey(), $solicitud->asignada_a);
        $this->navegar($sesionB, 'PUT', '/solicitudes/'.$solicitud->getKey(), ['decision' => 'aprobar'])->assertForbidden();
        $this->navegar($sesionA, 'POST', '/logout')->assertRedirect('/login');
        $this->navegar($sesionB, 'GET', '/solicitudes')->assertOk();
        $this->assertEquals($b->getKey(), $solicitud->fresh()->asignada_a);
        DB::table('rol_permisos')->where(['rol' => 'BIBLIOTECARIO', 'modulo' => 'solicitudes', 'accion' => 'editar'])->delete();
        $this->assertSame([], app(SesionesOperativas::class)->disponibles());
        $this->navegar($sesionB, 'PUT', '/solicitudes/'.$solicitud->getKey(), ['decision' => 'aprobar'])->assertForbidden();
        $this->assertSame('PENDIENTE', $solicitud->fresh()->estado);
    }

    public function test_cuenta_desactivada_y_lector_sin_relacion_no_pueden_solicitar(): void
    {
        $lector = $this->cuenta();
        $lector->update(['activo' => false]);
        $this->actingAs($lector)->post('/solicitudes', ['libros' => [1 => 1]])->assertForbidden();
        $lector->update(['activo' => true, 'id_usuario' => null]);
        $this->actingAs($lector)->get('/solicitudes/nueva')->assertForbidden();
        $this->post('/solicitudes', ['libros' => [1 => 1]])->assertForbidden();
        $this->assertSame(0, SolicitudPrestamo::count());
    }
}
