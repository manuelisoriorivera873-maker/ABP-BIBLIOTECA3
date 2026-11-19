<?php

namespace Tests\Feature;

use App\Models\Autor;
use App\Models\Bibliotecario;
use App\Models\Categoria;
use App\Models\CuentaAcceso;
use App\Models\Ejemplar;
use App\Models\Libro;
use App\Models\Persona;
use App\Models\Prestamo;
use App\Models\RecursoDigital;
use App\Models\SolicitudPrestamo;
use App\Models\Usuario;
use App\Services\SolicitarPrestamo;
use Database\Seeders\CatalogoVerificadoSeeder;
use Database\Seeders\CompletarCatalogoSeeder;
use Database\Seeders\EjemplaresDemostracionSeeder;
use Database\Seeders\EnriquecerCatalogoSeeder;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LectorCatalogoTest extends TestCase
{
    public function test_ejemplares_demo_idempotentes_y_ciclo_solicitud_prestamo_devolucion(): void
    {
        (new CatalogoVerificadoSeeder)->run();
        (new EnriquecerCatalogoSeeder)->run();
        (new CompletarCatalogoSeeder)->run();
        $seeder = new EjemplaresDemostracionSeeder;
        $seeder->run();
        $this->assertSame(44, Ejemplar::count());
        $antes = Ejemplar::orderBy('id_ejemplar')->get()->toJson();
        $seeder->run();
        $this->assertSame($antes, Ejemplar::orderBy('id_ejemplar')->get()->toJson());
        $this->assertSame(44, Ejemplar::distinct()->count('codigo_inventario'));
        $this->assertSame(44, Ejemplar::where('codigo_inventario', 'like', 'DEMO-PRUEBA-%')->count());
        $libro = Libro::where('isbn', '9788466322874')->firstOrFail();
        $lector = $this->cuenta();
        $personal = $this->cuenta('BIBLIOTECARIO');
        $this->actingAs($lector)->get('/lector/libros/'.$libro->ficha->publico)->assertOk()->assertSee('1 ejemplares disponibles');
        $this->post('/lector/seleccion/'.$libro->ficha->publico, ['cantidad' => 1])->assertSessionHasNoErrors();
        $token = $this->get('/lector/solicitar')->assertOk()->viewData('token');
        $this->post('/lector/enviar', ['confirmar' => 1, 'envio' => $token])->assertRedirect('/lector/mis-solicitudes');
        $solicitud = SolicitudPrestamo::firstOrFail();
        $this->actingAs($personal)->get('/solicitudes')->assertOk()->assertSee('La tregua');
        $this->put('/solicitudes/'.$solicitud->getKey(), ['decision' => 'aprobar', 'fecha_limite' => today()->addDays(7)->toDateString()])->assertRedirect('/solicitudes');
        $prestamo = Prestamo::firstOrFail();
        $this->assertSame(1, $prestamo->detalles()->count());
        $ejemplar = $libro->ejemplares()->firstOrFail();
        $this->assertSame('PRESTADO', $ejemplar->estado->nombre_estado);
        $this->assertSame(0, app(SolicitarPrestamo::class)->disponibles()->where('id_libro', $libro->getKey())->count());
        $seeder->run();
        $this->assertSame(44, Ejemplar::count());
        $this->assertSame('PRESTADO', $ejemplar->fresh()->estado->nombre_estado);
        $this->post('/devoluciones/'.$prestamo->getKey().'/registrar', ['fecha_devolucion' => today()->toDateString(), 'detalles' => [$prestamo->detalles()->first()->getKey()]])->assertRedirect('/devoluciones');
        $this->assertSame('DISPONIBLE', $ejemplar->fresh()->estado->nombre_estado);
        $this->assertSame(1, app(SolicitarPrestamo::class)->disponibles()->where('id_libro', $libro->getKey())->count());
        $this->actingAs($lector)->get('/lector/mis-prestamos')->assertOk()->assertSee('DEVUELTO');
    }

    public function test_ejemplares_demo_se_rechazan_en_produccion(): void
    {
        app()->instance('env', 'production');
        try {
            (new EjemplaresDemostracionSeeder)->run();
            $this->fail('No debe sembrar DEMO en producción.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('desarrollo', $e->getMessage());
        } finally {
            app()->instance('env', 'testing');
        }
        $this->assertSame(0, Ejemplar::count());
    }

    public function test_ampliacion_conserva_ediciones_pendientes_historial_y_disponibilidad_real(): void
    {
        $revision = collect(json_decode(file_get_contents(database_path('data/revision-catalogo.json')), true))->firstWhere('titulo', 'Aura');
        $persona = Persona::create(['nombre' => 'Carlos', 'apellido_paterno' => 'Fuentes', 'apellido_materno' => 'Macias']);
        $autor = Autor::create(['id_persona' => $persona->getKey()]);
        $original = Libro::create(['titulo' => 'Aura', 'isbn' => $revision['isbn_esperado']]);
        $original->autores()->attach($autor->getKey());
        $ejemplar = Ejemplar::create(['id_libro' => $original->getKey(), 'codigo_inventario' => 'HISTORICO', 'id_ubicacion' => 1, 'id_estado_ejemplar' => 2]);
        $lector = $this->cuenta();
        $bibliotecario = $this->cuenta('BIBLIOTECARIO');
        $prestamo = Prestamo::create(['id_usuario' => $lector->id_usuario, 'id_bibliotecario' => $bibliotecario->id_bibliotecario, 'fecha_prestamo' => today(), 'fecha_limite' => today()->addDays(7)]);
        $prestamo->detalles()->create(['id_ejemplar' => $ejemplar->getKey()]);
        $protegidos = collect(['ejemplares', 'prestamos', 'detalle_prestamo', 'devoluciones', 'cuentas_acceso', 'rol_permisos'])->mapWithKeys(fn ($t) => [$t => DB::table($t)->get()->toJson()]);
        $publico = $original->ficha->publico;
        $seeder = new CompletarCatalogoSeeder;
        $seeder->run();
        $this->assertSame(28, Libro::count());
        $this->assertSame($revision['isbn_esperado'], $original->fresh()->isbn);
        $this->assertSame($publico, $original->fresh()->ficha->publico);
        $this->assertNull($original->fresh()->ficha->isbn_verificado);
        $this->assertNull($original->fresh()->ficha->portada_url);
        $this->assertNotEmpty($original->fresh()->ficha->sinopsis);
        $tablas = ['libros', 'personas', 'autores', 'asigna_autores', 'asigna_categorias', 'asigna_editoriales', 'libro_fichas', 'autor_fichas', 'categoria_fichas', 'editorial_fichas'];
        $antes = collect($tablas)->mapWithKeys(fn ($t) => [$t => DB::table($t)->get()->toJson()]);
        $this->travel(2)->days();
        try {
            $seeder->run();
        } finally {
            $this->travelBack();
        }
        foreach ($antes->merge($protegidos) as $tabla => $filas) {
            $this->assertSame($filas, DB::table($tabla)->get()->toJson(), $tabla);
        }
        $datos = json_decode(file_get_contents(database_path('data/ampliacion.json')), true);
        $this->assertCount(27, $datos['libros']);
        foreach ($datos['libros'] as $dato) {
            $this->assertGreaterThanOrEqual(60, count(preg_split('/\s+/u', $dato['sinopsis'])));
            $this->assertLessThanOrEqual(100, count(preg_split('/\s+/u', $dato['sinopsis'])));
        }
        foreach ($datos['autores'] as $dato) {
            $this->assertGreaterThanOrEqual(60, count(preg_split('/\s+/u', $dato['biografia'])));
            $this->assertLessThanOrEqual(100, count(preg_split('/\s+/u', $dato['biografia'])));
        }
        $this->actingAs($lector)->get('/lector/inicio')->assertOk()->assertSee('Nuevos títulos del catálogo')->assertSee('Autores destacados')->assertSee('Descubre más libros');
        $nuevo = Libro::where('isbn', $datos['libros'][0]['isbn'])->firstOrFail();
        $this->get('/lector/explorar?disponibilidad=disponible')->assertOk()->assertViewHas('libros', fn ($p) => $p->total() === 0);
        $this->post('/lector/seleccion/'.$nuevo->ficha->publico, ['cantidad' => 1])->assertSessionHasErrors('cantidad');
        $this->get('/lector/libros/'.$nuevo->ficha->publico)->assertOk()->assertSee($datos['libros'][0]['sinopsis'])->assertSee('Idioma de esta edición');
        $this->get('/lector/explorar?buscar='.$datos['libros'][0]['isbn'])->assertOk()->assertSee('libro-sinopsis', false);
        $this->get('/lector/mis-prestamos')->assertOk()->assertSee('Aura');
    }

    public function test_conflicto_de_isbn_aborta_toda_la_ampliacion(): void
    {
        $dato = json_decode(file_get_contents(database_path('data/ampliacion.json')), true)['libros'][0];
        Libro::create(['titulo' => 'Otra identidad bibliográfica', 'isbn' => $dato['isbn']]);
        $antes = [Libro::count(), Autor::count(), Persona::count()];
        try {
            (new CompletarCatalogoSeeder)->run();
            $this->fail('El conflicto de ISBN debe detener la importación.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('conflicto', $error->getMessage());
        }
        $this->assertSame($antes, [Libro::count(), Autor::count(), Persona::count()]);
        $this->assertSame(0, DB::table('autor_fichas')->count());
    }

    public function test_enriquecimiento_idempotente_identidad_biografias_y_seguridad(): void
    {
        $original = $this->libro('Título histórico');
        $historico = DB::table('ejemplares')->get()->toJson();
        $persona = $original->autores->first()->id_persona;
        $seeder = new EnriquecerCatalogoSeeder;
        $seeder->run();
        $this->assertSame(16, Libro::count());
        $this->assertSame(12, Autor::count());
        $this->assertSame($persona, Autor::whereHas('ficha', fn ($q) => $q->where('nombre_publico', 'Juan Rulfo'))->firstOrFail()->id_persona);
        $tablas = ['libros', 'personas', 'autores', 'libro_fichas', 'autor_fichas', 'categoria_fichas', 'editorial_fichas', 'asigna_autores', 'asigna_categorias', 'asigna_editoriales'];
        $antes = collect($tablas)->mapWithKeys(fn ($t) => [$t => DB::table($t)->get()->toJson()]);
        $seeder->run();
        foreach ($antes as $tabla => $filas) {
            $this->assertSame($filas, DB::table($tabla)->get()->toJson(), $tabla);
        }
        $this->assertSame($historico, DB::table('ejemplares')->get()->toJson());
        $this->assertSame(0, Prestamo::count());
        $datos = json_decode(file_get_contents(database_path('data/enriquecimiento.json')), true);
        foreach ($datos['libros'] as $dato) {
            $this->assertGreaterThanOrEqual(50, count(preg_split('/\s+/u', $dato['sinopsis'])));
            $this->assertLessThanOrEqual(90, count(preg_split('/\s+/u', $dato['sinopsis'])));
        }
        foreach ($datos['autores'] as $dato) {
            $this->assertGreaterThanOrEqual(60, count(preg_split('/\s+/u', $dato['biografia'])));
            $this->assertLessThanOrEqual(100, count(preg_split('/\s+/u', $dato['biografia'])));
        }
        $lector = $this->cuenta();
        $this->actingAs($lector)->get('/lector/inicio')->assertOk()->assertSee('SAIDE Biblioteca')->assertSee('id="sidebar"', false)->assertSee('assets/css/lector.css', false);
        $autor = Autor::where('id_persona', $persona)->firstOrFail();
        $this->get('/lector/autores/'.$autor->ficha->publico)->assertOk()->assertSee('Juan Rulfo')->assertSee('16/05/1917')->assertSee('El gallo de oro')->assertDontSee('id_autor');
        $this->get('/lector/autores/'.$autor->getKey())->assertNotFound();
        $this->get('/lector/bibliografia/categorias')->assertOk()->assertSee('Narraciones extensas');
        $this->get('/lector/bibliografia/editoriales')->assertOk()->assertSee('formato de bolsillo');
        $this->get('/lector/explorar?buscar=9788466331845')->assertOk()->assertSee('Bestiario');
        $this->get('/lector/explorar?buscar=Julio+Cort%C3%A1zar')->assertOk()->assertViewHas('libros', fn ($p) => $p->total() === 3);
        $this->get('/lector/explorar?buscar=Bestiario&disponibilidad=agotado')->assertOk()->assertViewHas('libros', fn ($p) => $p->total() === 1 && $p->first()->disponibles === 0);
        DB::table('rol_permisos')->where('rol', 'USUARIO')->where('modulo', 'autores')->delete();
        $this->get('/lector/autores/'.$autor->ficha->publico)->assertForbidden();
        $this->actingAs($this->cuenta('BIBLIOTECARIO'))->get('/libros')->assertOk()->assertDontSee('assets/css/lector.css', false);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        DB::unprepared(file_get_contents(__DIR__.'/../Fixtures/schema.sql'));
        foreach (['2026_10_22_000002_create_permisos_por_rol.php', '2026_10_22_000003_create_solicitudes_prestamo.php', '2026_10_31_000004_create_libro_fichas.php', '2026_10_31_000005_create_fichas_bibliograficas.php', '2026_10_31_000006_add_revision_bibliografica.php', '2026_10_31_000007_create_lectura_y_favoritos.php'] as $migracion) {
            (require database_path('migrations/'.$migracion))->up();
        }
        DB::table('estados_usuario')->insert(['id_estado_usuario' => 1, 'nombre_estado_usuario' => 'ACTIVO']);
        DB::table('estados_ejemplar')->insert([['id_estado_ejemplar' => 1, 'nombre_estado' => 'DISPONIBLE'], ['id_estado_ejemplar' => 2, 'nombre_estado' => 'PRESTADO']]);
        DB::table('ubicaciones_libros')->insert(['id_ubicacion' => 1, 'nombre_ubicacion' => 'Estante']);
        DB::table('categorias')->insert(['id_categoria' => 1, 'nombre' => 'Novela']);
        DB::table('editoriales')->insert(['id_editorial' => 1, 'nombre' => 'Editorial verificada']);
    }

    private function cuenta(string $rol = 'USUARIO'): CuentaAcceso
    {
        $persona = Persona::create(['nombre' => 'Lector', 'apellido_paterno' => 'Prueba', 'apellido_materno' => (string) Persona::count()]);
        $relacion = $rol === 'USUARIO'
            ? ['id_usuario' => Usuario::create(['id_persona' => $persona->getKey(), 'telefono' => 'tel'.$persona->getKey(), 'id_estado_usuario' => 1])->getKey()]
            : ['id_bibliotecario' => Bibliotecario::create(['id_persona' => $persona->getKey()])->getKey()];

        return CuentaAcceso::create($relacion + ['nombre_usuario' => 'cuenta'.CuentaAcceso::count(), 'password' => 'UnaClaveSegura123!', 'rol' => $rol, 'activo' => true]);
    }

    private function libro(string $titulo = 'Novela de prueba', int $cantidad = 3): Libro
    {
        $persona = Persona::create(['nombre' => 'Juan', 'apellido_paterno' => 'Rulfo', 'apellido_materno' => 'Prueba'.Persona::count()]);
        $autor = Autor::create(['id_persona' => $persona->getKey()]);
        $libro = Libro::create(['titulo' => $titulo, 'isbn' => 'NO-VERIFICADO-'.Libro::count()]);
        $libro->autores()->attach($autor->getKey());
        $libro->categorias()->attach(1);
        $libro->editoriales()->attach(1);
        for ($i = 0; $i < $cantidad; $i++) {
            Ejemplar::create(['id_libro' => $libro->getKey(), 'codigo_inventario' => $libro->getKey().'-'.$i, 'id_ubicacion' => 1, 'id_estado_ejemplar' => 1]);
        }

        return $libro->fresh('ficha');
    }

    public function test_catalogo_ficha_y_bibliografia_no_exponen_ids_y_administracion_se_conserva(): void
    {
        $libro = $this->libro();
        $lector = $this->cuenta();
        $this->actingAs($lector)->post('/login', ['nombre_usuario' => $lector->nombre_usuario, 'password' => 'UnaClaveSegura123!'])->assertRedirect('/lector/inicio');
        foreach (['/lector/inicio', '/lector/explorar', '/lector/libros/'.$libro->ficha->publico, '/lector/bibliografia/autores', '/lector/bibliografia/categorias', '/lector/bibliografia/editoriales', '/lector/solicitar', '/lector/mis-solicitudes', '/lector/mis-prestamos'] as $ruta) {
            $html = $this->get($ruta)->assertOk()->getContent();
            $this->assertStringNotContainsString('<th>ID</th>', $html);
            $this->assertStringNotContainsString('id_libro', $html);
            $this->assertStringNotContainsString($libro->isbn, $html);
            $this->assertDoesNotMatchRegularExpression('~href="[^\"]*/(?:libros|autores|personas|categorias|editoriales)/\d+(?:[/?#"]|$)~', $html);
            $this->assertStringNotContainsString('Administración de personas', $html);
        }
        $this->get('/lector/libros/'.$libro->getKey())->assertNotFound();
        $this->get('/libros')->assertRedirect('/lector/explorar');
        $this->actingAs($this->cuenta('BIBLIOTECARIO'))->get('/libros')->assertOk()->assertSee('<th>ID</th>', false);
        $this->get('/lector/inicio')->assertForbidden();
        $this->actingAs($this->cuenta('SUPERADMIN'))->get('/libros')->assertOk()->assertSee('<th>ID</th>', false);
    }

    public function test_busqueda_compuesta_filtros_orden_paginacion_y_eager_loading(): void
    {
        for ($i = 0; $i < 14; $i++) {
            $this->libro('Título '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), $i === 0 ? 0 : 1);
        }
        $this->actingAs($this->cuenta());
        $this->get('/lector/explorar?buscar=Juan+Rulfo&categoria=Novela&editorial=Editorial+verificada&orden=autor')->assertOk()->assertViewHas('libros', fn ($p) => $p->total() === 14 && $p->count() === 12)->assertSee('categoria=Novela', false);
        $this->get('/lector/explorar?disponibilidad=agotado')->assertOk()->assertViewHas('libros', fn ($p) => $p->total() === 1);
        $this->get('/lector/explorar?disponibilidad=disponible&orden=disponibilidad&page=2')->assertOk()->assertViewHas('libros', fn ($p) => $p->total() === 13 && $p->count() === 1);
        $this->get('/lector/explorar?buscar=sincoincidencia')->assertOk()->assertSee('No encontramos libros');
        $this->get('/lector/explorar?orden=id_libro')->assertSessionHasErrors('orden');
        DB::enableQueryLog();
        $this->get('/lector/explorar')->assertOk();
        $consultas = DB::getQueryLog();
        // Una sola carga por relación, nunca una consulta de autor por tarjeta.
        $this->assertLessThanOrEqual(2, collect($consultas)->filter(fn ($q) => str_contains($q['query'], 'from "autores"'))->count());
        DB::disableQueryLog();
    }

    public function test_bandeja_uuid_envio_unico_varios_titulos_aprobacion_y_privacidad(): void
    {
        $a = $this->libro('Libro uno');
        $b = $this->libro('Libro dos');
        $lector = $this->cuenta();
        $personal = $this->cuenta('BIBLIOTECARIO');
        $this->actingAs($lector);
        $this->post('/lector/seleccion/'.$a->ficha->publico, ['cantidad' => 3])->assertSessionHasNoErrors();
        $this->post('/lector/seleccion/'.$b->ficha->publico, ['cantidad' => 3])->assertSessionHasErrors('cantidad');
        $this->post('/lector/seleccion/'.$b->ficha->publico, ['cantidad' => 2])->assertSessionHasNoErrors();
        $this->get('/lector/solicitar')->assertOk()->assertSee('Total: 5 ejemplares')->assertDontSee('id_libro');
        $token = session('lector.envio');
        $this->post('/lector/enviar', ['confirmar' => 1, 'envio' => $token])->assertRedirect('/lector/mis-solicitudes');
        $this->post('/lector/enviar', ['confirmar' => 1, 'envio' => $token])->assertRedirect('/lector/mis-solicitudes');
        $this->assertSame(1, SolicitudPrestamo::count());
        $solicitud = SolicitudPrestamo::first();
        $this->assertSame(5, $solicitud->detalles->sum('cantidad'));
        $this->assertSame(0, Prestamo::count());
        $this->actingAs($this->cuenta('SUPERADMIN'))->put('/solicitudes/'.$solicitud->getKey(), ['decision' => 'aprobar', 'fecha_limite' => today()->addDays(7)->toDateString()])->assertForbidden();
        $this->actingAs($personal)->put('/solicitudes/'.$solicitud->getKey(), ['decision' => 'aprobar', 'fecha_limite' => today()->addDays(7)->toDateString()])->assertRedirect('/solicitudes');
        $this->assertSame(1, Prestamo::count());
        $this->assertSame(5, Prestamo::first()->detalles()->count());
        $this->assertSame('APROBADA', $solicitud->fresh()->estado);
        $this->put('/solicitudes/'.$solicitud->getKey(), ['decision' => 'aprobar', 'fecha_limite' => today()->addDays(7)->toDateString()])->assertStatus(409);
        $this->put('/solicitudes/'.$solicitud->getKey(), ['decision' => 'rechazar', 'motivo' => 'Intento posterior'])->assertStatus(409);
        $this->actingAs($lector)->get('/lector/mis-prestamos')->assertOk()->assertSee('Libro uno')->assertDontSee('Préstamo #');
        $this->get('/lector/explorar')->assertOk()->assertViewHas('libros', fn ($p) => $p->firstWhere('titulo', 'Libro uno')->disponibles === 0);
        $this->actingAs($this->cuenta())->get('/lector/mis-prestamos')->assertOk()->assertViewHas('prestamos', fn ($p) => $p->total() === 0);
        $this->get('/lector/mis-solicitudes')->assertOk()->assertViewHas('solicitudes', fn ($p) => $p->total() === 0);
    }

    public function test_no_hay_acceso_administrativo_aun_con_casillas_extra_y_revocacion_es_inmediata(): void
    {
        $lector = $this->cuenta();
        $this->actingAs($lector);
        foreach (['libros', 'personas', 'usuarios', 'bibliotecarios', 'ejemplares'] as $modulo) {
            foreach (['ver', 'crear', 'editar', 'eliminar'] as $accion) {
                DB::table('rol_permisos')->insertOrIgnore(compact('modulo', 'accion') + ['rol' => 'USUARIO']);
            }
        }
        foreach (['/libros/create', '/personas', '/usuarios', '/bibliotecarios', '/ejemplares', '/cuentas'] as $ruta) {
            $this->get($ruta)->assertForbidden();
        }
        DB::table('rol_permisos')->where(['rol' => 'USUARIO', 'modulo' => 'solicitudes', 'accion' => 'ver'])->delete();
        $this->get('/lector/inicio')->assertOk()->assertDontSee('Mis solicitudes')->assertDontSee('Mis préstamos');
        $this->get('/lector/mis-solicitudes')->assertForbidden();
        DB::table('rol_permisos')->where(['rol' => 'USUARIO', 'modulo' => 'libros', 'accion' => 'ver'])->delete();
        $this->get('/lector/explorar')->assertForbidden();
        $this->post('/lector/seleccion/123', ['cantidad' => 1])->assertNotFound();
    }

    public function test_datos_verificados_idempotentes_sin_ejemplares_ficticios(): void
    {
        $existente = $this->libro('Pedro Paramo');
        $antes = Ejemplar::count();
        (new CatalogoVerificadoSeeder)->run();
        $conteos = [Libro::count(), Autor::count(), Persona::count(), Ejemplar::count()];
        (new CatalogoVerificadoSeeder)->run();
        $this->assertSame($conteos, [Libro::count(), Autor::count(), Persona::count(), Ejemplar::count()]);
        $this->assertSame($antes, Ejemplar::count());
        $this->assertSame('NO-VERIFICADO-0', $existente->fresh()->isbn);
        $this->assertNull($existente->fresh()->ficha->isbn_verificado);
        $this->assertSame('Pedro Páramo', $existente->fresh()->titulo_publico);
        $nuevo = Libro::where('isbn', '9788466360272')->firstOrFail();
        $this->assertNotNull($nuevo->ficha->portada_url);
        $this->assertSame(0, $nuevo->ejemplares()->count());
    }

    public function test_seleccion_se_puede_actualizar_y_quitar_y_cambios_se_notifican(): void
    {
        $libro = $this->libro();
        $lector = $this->cuenta();
        $url = '/lector/seleccion/'.$libro->ficha->publico;
        $this->actingAs($lector)->post($url, ['cantidad' => 2])->assertSessionHasNoErrors();
        $this->post($url, ['cantidad' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1, array_sum(session('lector.bandeja')));
        $this->post($url, ['cantidad' => 0])->assertSessionHasNoErrors();
        $this->assertSame([], session('lector.bandeja'));
        $solicitud = SolicitudPrestamo::create(['id_usuario' => $lector->id_usuario]);
        $solicitud->detalles()->create(['id_libro' => $libro->getKey(), 'cantidad' => 1]);
        $this->get('/lector/mis-solicitudes')->assertOk()->assertDontSee('Hay cambios en el seguimiento');
        $solicitud->update(['estado' => 'RECHAZADA', 'motivo_rechazo' => 'No es posible atender la solicitud.', 'atendida_en' => now()]);
        $this->get('/lector/mis-solicitudes')->assertOk()->assertSee('Hay cambios en el seguimiento')->assertSee('No es posible atender la solicitud.');
        $this->get('/lector/mis-solicitudes')->assertOk()->assertDontSee('Hay cambios en el seguimiento');
    }

    public function test_fallo_en_detalles_revierte_el_envio_y_permite_reintentar_sin_duplicar(): void
    {
        $libro = $this->libro();
        $this->actingAs($this->cuenta())->post('/lector/seleccion/'.$libro->ficha->publico, ['cantidad' => 1]);
        $this->get('/lector/solicitar')->assertOk();
        $datos = ['confirmar' => 1, 'envio' => session('lector.envio')];
        DB::unprepared("CREATE TRIGGER fallo_envio BEFORE INSERT ON solicitud_detalles BEGIN SELECT RAISE(ABORT, 'fallo deliberado'); END");
        $this->post('/lector/enviar', $datos)->assertStatus(500);
        $this->assertSame(0, SolicitudPrestamo::count());
        $this->assertSame(0, DB::table('solicitud_envios')->count());
        $this->assertSame(1, array_sum(session('lector.bandeja')));
        DB::unprepared('DROP TRIGGER fallo_envio');
        $this->post('/lector/enviar', $datos)->assertRedirect('/lector/mis-solicitudes');
        $this->assertSame(1, SolicitudPrestamo::count());
        $this->assertSame(1, DB::table('solicitud_envios')->count());
    }

    public function test_cambiar_la_identidad_bibliografica_no_reutiliza_una_portada_o_isbn_anteriores(): void
    {
        (new CatalogoVerificadoSeeder)->run();
        $libro = Libro::where('isbn', '9788466360272')->firstOrFail();
        $publico = $libro->ficha->publico;
        $libro->update(['titulo' => 'Título corregido por la biblioteca']);
        $ficha = $libro->fresh()->ficha;
        $this->assertSame($publico, $ficha->publico);
        $this->assertNull($ficha->portada_url);
        $this->assertNull($ficha->isbn_verificado);
        (new CatalogoVerificadoSeeder)->run();
        $this->assertNull($libro->fresh()->ficha->portada_url);
    }

    public function test_favoritos_persisten_son_privados_y_no_generan_prestamos(): void
    {
        $libro = $this->libro('Favorito privado', 0);
        $uno = $this->cuenta();
        $dos = $this->cuenta();
        $url = '/lector/favoritos/'.$libro->ficha->publico;
        $this->actingAs($uno)->putJson($url)->assertOk()->assertJson(['favorito' => true]);
        $this->putJson($url)->assertOk();
        $this->assertSame(1, DB::table('usuario_favoritos')->count());
        $this->get('/lector/favoritos')->assertOk()->assertSee('Favorito privado')->assertSee('aria-pressed="true"', false);
        $this->actingAs($dos)->get('/lector/favoritos')->assertOk()->assertDontSee('Favorito privado');
        $this->putJson($url, ['id_usuario' => $uno->id_usuario])->assertUnprocessable();
        $this->deleteJson($url)->assertOk();
        $this->assertSame(1, DB::table('usuario_favoritos')->count());
        $this->actingAs($uno)->get('/lector/favoritos')->assertSee('Favorito privado');
        $this->deleteJson($url)->assertOk();
        $this->get('/lector/favoritos')->assertDontSee('Favorito privado');
        $this->assertSame(0, Prestamo::count());
        $this->assertSame(0, SolicitudPrestamo::count());
        $this->actingAs($this->cuenta('BIBLIOTECARIO'))->putJson($url)->assertForbidden();
        $this->actingAs($this->cuenta('SUPERADMIN'))->get('/lector/favoritos')->assertForbidden();
        $this->actingAs($uno);
        DB::table('rol_permisos')->where('rol', 'USUARIO')->where('modulo', 'libros')->where('accion', 'ver')->delete();
        $this->get('/lector/favoritos')->assertForbidden();
        $this->putJson($url)->assertForbidden();
    }

    public function test_filtro_idioma_se_aplica_a_ediciones_sin_reetiquetar_ingles(): void
    {
        $es = $this->libro('Español auténtico', 0);
        $en = $this->libro('English edition', 0);
        $es->ficha()->update(['idioma' => 'spa']);
        $en->ficha()->update(['idioma' => 'eng']);
        $this->actingAs($this->cuenta())->get('/lector/explorar?idioma=spa')->assertOk()->assertSee('Español auténtico')->assertDontSee('English edition');
        $this->get('/lector/explorar?idioma=eng')->assertSee('English edition')->assertDontSee('Español auténtico');
        $this->getJson('/lector/explorar?idioma=falso')->assertUnprocessable();
        $this->assertSame('eng', $en->fresh()->ficha->idioma);
    }

    public function test_lectura_autorizada_archivos_privados_avance_propio_y_revocacion(): void
    {
        $libro = $this->libro('Texto autorizado', 0);
        $uno = $this->cuenta();
        $dos = $this->cuenta();
        $directorio = storage_path('app/private/lecturas');
        if (! is_dir($directorio)) {
            mkdir($directorio, 0700, true);
        }
        $nombre = 'prueba-'.bin2hex(random_bytes(8)).'.pdf';
        $ruta = $directorio.'/'.$nombre;
        file_put_contents($ruta, "%PDF-1.4\n%Documento de prueba aislado\n%%EOF");
        try {
            $r = RecursoDigital::create(['publico' => (string) Str::uuid(), 'id_libro' => $libro->getKey(), 'tipo' => 'integrado', 'formato' => 'pdf', 'idioma' => 'spa', 'edicion' => 'Archivo de prueba', 'ruta_privada' => $nombre, 'sha256' => hash_file('sha256', $ruta), 'fuente' => 'https://example.org/fuente', 'licencia' => 'Texto de prueba propio', 'licencia_url' => 'https://example.org/licencia', 'disponible' => true, 'verificado_en' => now()]);
            $url = '/lector/leer/'.$r->publico;
            $avance = '/lector/lectura/'.$r->publico.'/progreso';
            $archivo = '/lector/lectura/'.$r->publico.'/archivo';
            $this->actingAs($uno)->get('/lector/libros/'.$libro->ficha->publico)->assertOk()->assertSee('Leer en línea');
            $this->get($url)->assertOk()->assertSee('lectura-visor')->assertSee('Archivo de prueba');
            $this->get($archivo)->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->putJson($avance, ['posicion' => '2', 'tamano' => 125])->assertOk();
            $this->get($url)->assertSee('data-posicion="2"', false);
            $this->actingAs($dos)->get($url)->assertSee('data-posicion=""', false);
            $this->putJson($avance, ['posicion' => '3', 'tamano' => 150])->assertOk();
            $this->assertSame(2, DB::table('lectura_progresos')->count());
            $this->putJson($avance, ['posicion' => '<script>', 'tamano' => 100])->assertUnprocessable();
            $this->putJson($avance, ['posicion' => '1', 'tamano' => 900, 'id_usuario' => $uno->id_usuario])->assertUnprocessable();
            $r->update(['ruta_privada' => '../backups/no-publico.sql']);
            $this->get($archivo)->assertNotFound();
            $r->update(['ruta_privada' => $nombre, 'disponible' => false]);
            $this->get($url)->assertNotFound();
            $this->get($archivo)->assertNotFound();
            $this->putJson($avance, ['posicion' => '1', 'tamano' => 100])->assertNotFound();
            $this->get('/lector/libros/'.$libro->ficha->publico)->assertDontSee('Leer en línea');
            $this->assertSame(0, Prestamo::count());
        } finally {
            unlink($ruta);
        }
    }

    public function test_nuevas_ediciones_espanolas_idempotentes_sin_inventario_ni_perdida_de_ediciones_inglesas(): void
    {
        (new CatalogoVerificadoSeeder)->run();
        (new EnriquecerCatalogoSeeder)->run();
        (new CompletarCatalogoSeeder)->run();
        (new EjemplaresDemostracionSeeder)->run();
        $antes = [Libro::count(), Autor::count(), Ejemplar::orderBy('id_ejemplar')->get()->toJson()];
        $ingles = Libro::where('isbn', '9780141439471')->firstOrFail();
        $identidad = $ingles->toJson();
        $datos = json_decode(file_get_contents(database_path('data/lectura-catalogo.json')), true);
        (new EnriquecerCatalogoSeeder)->importar($datos);
        $this->assertSame($antes[0] + 32, Libro::count());
        $primera = [Libro::count(), Autor::count(), Persona::count(), DB::table('asigna_autores')->count()];
        (new EnriquecerCatalogoSeeder)->importar($datos);
        $this->assertSame($primera, [Libro::count(), Autor::count(), Persona::count(), DB::table('asigna_autores')->count()]);
        $this->assertSame($antes[2], Ejemplar::orderBy('id_ejemplar')->get()->toJson());
        $this->assertSame($identidad, $ingles->fresh()->toJson());
        $this->assertSame('eng', $ingles->fresh()->ficha->idioma);
        foreach ($datos['libros'] as $dato) {
            $libro = Libro::where('isbn', $dato['isbn'])->firstOrFail();
            $this->assertSame(0, $libro->ejemplares()->count());
            $this->assertSame('spa', $libro->ficha->idioma);
            $this->assertSame(count($dato['autores'] ?? [$dato['autor']]), $libro->autores()->count());
            $this->assertNotNull($libro->ficha->obra_fuente);
        }
        $this->actingAs($this->cuenta())->get('/lector/explorar?idioma=spa&buscar=Drácula')->assertOk()->assertSee('Drácula');
        $this->get('/lector/explorar?categoria=Novela%20detectivesca')->assertOk()->assertSee('Estudio en escarlata');
        $this->assertSame(0, Prestamo::count());
    }

    public function test_solo_se_ofrecen_recursos_validos_y_la_previa_no_se_presenta_como_lectura_completa(): void
    {
        $libro = $this->libro('Vista previa autorizada', 0);
        $r = RecursoDigital::create(['publico' => (string) Str::uuid(), 'id_libro' => $libro->getKey(), 'tipo' => 'previa', 'formato' => 'pdf', 'idioma' => 'spa', 'edicion' => 'Primeras páginas oficiales', 'url' => 'https://example.org/muestra.pdf', 'fuente' => 'https://example.org/fuente', 'licencia' => 'Solo enlace al fragmento oficial', 'licencia_url' => 'https://example.org/licencia', 'disponible' => true, 'verificado_en' => now()]);
        $cuenta = $this->cuenta();
        $ficha = '/lector/libros/'.$libro->ficha->publico;
        $url = '/lector/leer/'.$r->publico;
        $this->actingAs($cuenta)->get($ficha)->assertOk()->assertSee('Vista previa oficial (no es el libro completo)');
        $this->get($url)->assertRedirect('https://example.org/muestra.pdf');
        $this->get('/lector/lectura/'.$r->publico.'/archivo')->assertNotFound();
        $r->update(['url' => 'http://example.org/insegura']);
        $this->get($url)->assertNotFound();
        $this->get($ficha)->assertDontSee('Vista previa oficial');
        $r->update(['url' => 'https://example.org/muestra.pdf', 'verificado_en' => null]);
        $this->get($url)->assertNotFound();
        $r->update(['verificado_en' => now(), 'licencia' => '']);
        $this->get($url)->assertNotFound();
        $this->actingAs($this->cuenta('BIBLIOTECARIO'))->get($url)->assertForbidden();
        $this->actingAs($this->cuenta('SUPERADMIN'))->get($ficha)->assertForbidden();
    }

    public function test_generos_equivalentes_se_muestran_una_sola_vez_sin_borrar_registros(): void
    {
        $libro = $this->libro('Cuento fantástico', 0);
        $uno = Categoria::create(['nombre' => 'Cuento']);
        $alias = Categoria::create(['nombre' => 'Cuentos']);
        $libro->categorias()->syncWithoutDetaching([$uno->getKey(), $alias->getKey()]);
        $this->actingAs($this->cuenta())->get('/lector/explorar?categoria=Cuento')->assertOk()->assertSee('Cuento fantástico')->assertDontSee('<option >Cuentos</option>', false);
        $this->get('/lector/explorar?categoria=Cuentos')->assertOk()->assertSee('Cuento fantástico');
        $this->assertTrue(Categoria::whereKey($alias->getKey())->exists());
        $this->assertTrue($libro->categorias()->where('categorias.id_categoria', $alias->getKey())->exists());
    }

    public function test_ultimos_32_titulos_reciben_ejemplares_demo_y_dracula_dune_se_prestan_juntos(): void
    {
        (new CatalogoVerificadoSeeder)->run();
        (new EnriquecerCatalogoSeeder)->run();
        (new CompletarCatalogoSeeder)->run();
        $seeder = new EjemplaresDemostracionSeeder;
        $seeder->run();
        $historicos = Ejemplar::orderBy('id_ejemplar')->get();
        $this->assertSame(44, $historicos->count());
        $datos = json_decode(file_get_contents(database_path('data/lectura-catalogo.json')), true);
        (new EnriquecerCatalogoSeeder)->importar($datos);
        $seeder->run();
        $this->assertSame(76, Ejemplar::count());
        $this->assertSame($historicos->toJson(), Ejemplar::whereIn('id_ejemplar', $historicos->modelKeys())->orderBy('id_ejemplar')->get()->toJson());
        $primera = Ejemplar::orderBy('id_ejemplar')->get()->toJson();
        $seeder->run();
        $this->assertSame($primera, Ejemplar::orderBy('id_ejemplar')->get()->toJson());
        $lector = $this->cuenta();
        $personal = $this->cuenta('BIBLIOTECARIO');
        foreach ($datos['libros'] as $dato) {
            $libro = Libro::where('isbn', $dato['isbn'])->firstOrFail();
            $this->assertSame(1, $libro->ejemplares()->count());
            $this->assertSame(1, app(SolicitarPrestamo::class)->disponibles()->where('id_libro', $libro->getKey())->count());
            $this->actingAs($lector)->get('/lector/libros/'.$libro->ficha->publico)->assertOk()->assertSee('1 ejemplares disponibles');
        }
        $seleccionados = Libro::whereIn('isbn', ['9788491819103', '9788497596824'])->get();
        foreach ($seleccionados as $libro) {
            $this->post('/lector/seleccion/'.$libro->ficha->publico, ['cantidad' => 1])->assertSessionHasNoErrors();
        }
        $token = $this->get('/lector/solicitar')->assertOk()->viewData('token');
        $this->post('/lector/enviar', ['confirmar' => 1, 'envio' => $token])->assertRedirect('/lector/mis-solicitudes')->assertSessionHasNoErrors();
        $solicitud = SolicitudPrestamo::firstOrFail();
        $this->assertSame(2, $solicitud->detalles()->count());
        $this->actingAs($personal)->get('/solicitudes')->assertOk()->assertSee('Drácula')->assertSee('Dune');
        $aprobar = ['decision' => 'aprobar', 'fecha_limite' => today()->addDays(7)->toDateString()];
        $this->put('/solicitudes/'.$solicitud->getKey(), $aprobar)->assertSessionHasNoErrors();
        $prestamo = Prestamo::firstOrFail();
        $this->assertSame(1, Prestamo::count());
        $this->assertSame(2, $prestamo->detalles()->count());
        $this->assertSame('APROBADA', $solicitud->fresh()->estado);
        foreach ($seleccionados as $libro) {
            $this->assertSame('PRESTADO', $libro->ejemplares()->first()->estado->nombre_estado);
            $this->assertSame(0, app(SolicitarPrestamo::class)->disponibles()->where('id_libro', $libro->getKey())->count());
        }
        $this->put('/solicitudes/'.$solicitud->getKey(), $aprobar)->assertStatus(409);
        $seeder->run();
        $this->assertSame(76, Ejemplar::count());
        $this->assertSame(0, app(SolicitarPrestamo::class)->disponibles()->whereIn('id_libro', $seleccionados->modelKeys())->count());
        $this->post('/devoluciones/'.$prestamo->getKey().'/registrar', ['fecha_devolucion' => today()->toDateString(), 'detalles' => $prestamo->detalles()->pluck('id_detalle')->all()])->assertSessionHasNoErrors();
        $this->assertSame(2, app(SolicitarPrestamo::class)->disponibles()->whereIn('id_libro', $seleccionados->modelKeys())->count());
    }

    private function prepararObjetivosDemo(): EjemplaresDemostracionSeeder
    {
        (require database_path('migrations/2026_11_01_000008_create_inventario_demo_objetivos.php'))->up();
        (new CatalogoVerificadoSeeder)->run();
        $seeder = new EjemplaresDemostracionSeeder;
        $seeder->run();

        return $seeder;
    }

    public function test_objetivos_aleatorios_persisten_entre_ejecuciones_y_no_duplican_copias(): void
    {
        $seeder = $this->prepararObjetivosDemo();
        $originales = Ejemplar::orderBy('id_ejemplar')->get();
        $resultado = $seeder->completarCantidadesAleatorias();
        $this->assertCount(2, $resultado);
        foreach ($resultado as $r) {
            $this->assertGreaterThanOrEqual(1, $r['objetivo']);
            $this->assertLessThanOrEqual(20, $r['objetivo']);
            $this->assertSame($r['objetivo'], $r['despues']);
            $this->assertSame($r['objetivo'] - 1, $r['creados']);
        }
        $antes = [DB::table('inventario_demo_objetivos')->orderBy('id_libro')->get()->toJson(), Ejemplar::orderBy('id_ejemplar')->get()->toJson()];
        $this->travel(2)->days();
        try {
            $segunda = (new EjemplaresDemostracionSeeder)->completarCantidadesAleatorias();
            $this->assertSame(0, array_sum(array_column($segunda, 'creados')));
            $this->assertSame($antes, [DB::table('inventario_demo_objetivos')->orderBy('id_libro')->get()->toJson(), Ejemplar::orderBy('id_ejemplar')->get()->toJson()]);
        } finally {
            $this->travelBack();
        }
        $this->assertSame($originales->toJson(), Ejemplar::whereIn('id_ejemplar', $originales->modelKeys())->orderBy('id_ejemplar')->get()->toJson());
        $this->assertSame(Ejemplar::count(), Ejemplar::distinct()->count('codigo_inventario'));
        app()->instance('env', 'production');
        try {
            $seeder->completarCantidadesAleatorias();
            $this->fail('Debe rechazar producción.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('desarrollo', $e->getMessage());
        } finally {
            app()->instance('env', 'testing');
        }
    }

    public function test_objetivos_demo_conservan_mas_de_20_y_excluyen_inventario_sin_marca(): void
    {
        $seeder = $this->prepararObjetivosDemo();
        $libros = Libro::orderBy('id_libro')->get();
        $primero = $libros[0];
        $base = $primero->ejemplares()->first();
        for ($n = 2; $n <= 21; $n++) {
            Ejemplar::create(['id_libro' => $primero->getKey(), 'id_ubicacion' => $base->id_ubicacion, 'id_estado_ejemplar' => 1, 'codigo_inventario' => 'DEMO-PRUEBA-'.$primero->isbn.'-'.$n]);
        }
        $libros[1]->ejemplares()->first()->update(['codigo_inventario' => 'INVENTARIO-VERIFICADO']);
        $antes = Ejemplar::orderBy('id_ejemplar')->get()->toJson();
        $r = collect($seeder->completarCantidadesAleatorias())->keyBy('id_libro');
        $this->assertTrue($r[$primero->getKey()]['conservar_mas_de_20']);
        $this->assertSame(21, $r[$primero->getKey()]['despues']);
        $this->assertArrayHasKey('excluido', $r[$libros[1]->getKey()]);
        $this->assertSame($antes, Ejemplar::orderBy('id_ejemplar')->get()->toJson());
    }

    public function test_objetivos_demo_permiten_prestar_cinco_copias_y_devolver_sin_reactivar_prestadas(): void
    {
        $seeder = $this->prepararObjetivosDemo();
        $libros = Libro::orderBy('id_libro')->get();
        foreach ($libros as $i => $l) {
            DB::table('inventario_demo_objetivos')->insert(['id_libro' => $l->getKey(), 'isbn' => $l->isbn, 'objetivo' => $i === 0 ? 3 : 2, 'existentes_al_asignar' => 1, 'asignado_en' => now()]);
        }
        $seeder->completarCantidadesAleatorias();
        $lector = $this->cuenta();
        $personal = $this->cuenta('BIBLIOTECARIO');
        $this->actingAs($lector)->post('/lector/seleccion/'.$libros[0]->ficha->publico, ['cantidad' => 4])->assertSessionHasErrors();
        foreach ($libros as $i => $l) {
            $this->post('/lector/seleccion/'.$l->ficha->publico, ['cantidad' => $i === 0 ? 3 : 2])->assertSessionHasNoErrors();
        }
        $token = $this->get('/lector/solicitar')->assertOk()->viewData('token');
        $this->post('/lector/enviar', ['confirmar' => 1, 'envio' => $token])->assertSessionHasNoErrors();
        $solicitud = SolicitudPrestamo::firstOrFail();
        $this->actingAs($personal)->put('/solicitudes/'.$solicitud->getKey(), ['decision' => 'aprobar', 'fecha_limite' => today()->addDays(7)->toDateString()])->assertSessionHasNoErrors();
        $prestamo = Prestamo::firstOrFail();
        $this->assertSame(5, $prestamo->detalles()->count());
        $this->assertSame(5, $prestamo->detalles()->distinct()->count('id_ejemplar'));
        $this->assertSame(0, app(SolicitarPrestamo::class)->disponibles()->count());
        $copias = Ejemplar::orderBy('id_ejemplar')->get()->toJson();
        $this->assertSame(0, array_sum(array_column($seeder->completarCantidadesAleatorias(), 'creados')));
        $this->assertSame($copias, Ejemplar::orderBy('id_ejemplar')->get()->toJson());
        $this->post('/devoluciones/'.$prestamo->getKey().'/registrar', ['fecha_devolucion' => today()->toDateString(), 'detalles' => $prestamo->detalles()->pluck('id_detalle')->all()])->assertSessionHasNoErrors();
        $this->assertSame(5, app(SolicitarPrestamo::class)->disponibles()->count());
        $this->assertSame([3, 2], DB::table('inventario_demo_objetivos')->orderBy('id_libro')->pluck('objetivo')->all());
    }
}
