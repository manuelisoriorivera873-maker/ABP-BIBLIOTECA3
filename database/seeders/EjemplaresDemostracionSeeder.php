<?php

namespace Database\Seeders;

use App\Models\Ejemplar;
use App\Models\EstadoEjemplar;
use App\Models\Libro;
use App\Models\UbicacionLibro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EjemplaresDemostracionSeeder extends Seeder
{
    public static function titulos(): array
    {
        $primera = require database_path('data/bibliografia.php');
        $segunda = json_decode(file_get_contents(database_path('data/enriquecimiento.json')), true, 512, JSON_THROW_ON_ERROR);
        $tercera = json_decode(file_get_contents(database_path('data/ampliacion.json')), true, 512, JSON_THROW_ON_ERROR);

        $cuarta = json_decode(file_get_contents(database_path('data/lectura-catalogo.json')), true, 512, JSON_THROW_ON_ERROR);

        return array_merge($primera['nuevos'], $segunda['libros'], $tercera['libros'], $cuarta['libros']);
    }

    public function run(): void
    {
        if (! app()->environment(['local', 'development', 'testing'])) {
            throw new RuntimeException('Los ejemplares DEMO solo se permiten en desarrollo o pruebas.');
        }
        DB::transaction(function (): void {
            $estados = EstadoEjemplar::whereRaw('UPPER(TRIM(nombre_estado)) = ?', ['DISPONIBLE'])->get();
            if ($estados->count() !== 1) {
                throw new RuntimeException('No hay un estado DISPONIBLE inequívoco.');
            }
            $normalizar = fn ($s) => Str::lower(Str::ascii(trim($s)));
            $ubicacion = null;
            foreach (self::titulos() as $dato) {
                // El mismo bloqueo de libro serializa dos ejecuciones del seeder.
                $libro = Libro::where('isbn', $dato['isbn'])->lockForUpdate()->first();
                // Solo se siembran libros registrados; el instalador comprueba el manifiesto completo.
                if (! $libro) {
                    continue;
                }
                if ($normalizar($libro->titulo) !== $normalizar($dato['titulo'])) {
                    throw new RuntimeException('Edición ausente o en conflicto: '.$dato['titulo']);
                }
                // No reactivar ni reemplazar copias prestadas, apartadas o de baja.
                if ($libro->ejemplares()->exists()) {
                    continue;
                }
                $codigo = 'DEMO-PRUEBA-'.$dato['isbn'].'-01';
                if (Ejemplar::where('codigo_inventario', $codigo)->exists()) {
                    throw new RuntimeException('Código DEMO ya utilizado por otra relación.');
                }
                $ubicacion ??= UbicacionLibro::firstOrCreate(
                    ['nombre_ubicacion' => 'DEMOSTRACION - DATOS DE PRUEBA'],
                    ['descripcion' => 'Inventario simulado de desarrollo; sin validación física ni ubicación real.']
                );
                Ejemplar::create([
                    'codigo_inventario' => $codigo, 'id_libro' => $libro->getKey(),
                    'id_ubicacion' => $ubicacion->getKey(), 'id_estado_ejemplar' => $estados->first()->getKey(),
                ]);
            }
        }, 3);
    }

    public function completarCantidadesAleatorias(): array
    {
        if (! app()->environment(['local', 'development', 'testing'])) {
            throw new RuntimeException('Las cantidades DEMO solo se permiten en desarrollo o pruebas.');
        }

        return DB::transaction(function (): array {
            $datos = collect(self::titulos())->keyBy('isbn');
            $normalizar = fn ($s) => Str::lower(Str::ascii(trim($s)));
            $estados = EstadoEjemplar::whereRaw('UPPER(TRIM(nombre_estado)) = ?', ['DISPONIBLE'])->get();
            if ($estados->count() !== 1) {
                throw new RuntimeException('Estado DISPONIBLE ambiguo o ausente.');
            }
            $ubicacion = UbicacionLibro::where('nombre_ubicacion', 'DEMOSTRACION - DATOS DE PRUEBA')->sole();
            $resultado = [];
            foreach (Libro::orderBy('id_libro')->lockForUpdate()->get() as $libro) {
                $dato = $datos->get($libro->isbn);
                if (! $dato) {
                    $resultado[] = ['id_libro' => $libro->getKey(), 'titulo' => $libro->titulo, 'excluido' => 'Fuera de los manifiestos de demostración; se conserva el inventario histórico.'];

                    continue;
                }
                if ($normalizar($dato['titulo']) !== $normalizar($libro->titulo)) {
                    throw new RuntimeException('Identidad de edición en conflicto: '.$libro->titulo);
                }
                $copias = $libro->ejemplares()->lockForUpdate()->get();
                if ($copias->contains(fn ($e) => ! str_starts_with($e->codigo_inventario, 'DEMO-PRUEBA-') || $e->id_ubicacion !== $ubicacion->getKey())) {
                    $resultado[] = ['id_libro' => $libro->getKey(), 'titulo' => $libro->titulo, 'excluido' => 'Contiene inventario sin identificación DEMO o con otra ubicación.'];

                    continue;
                }
                $existentes = $copias->count();
                $fila = DB::table('inventario_demo_objetivos')->where('id_libro', $libro->getKey())->first();
                if (! $fila) {
                    DB::table('inventario_demo_objetivos')->insert(['id_libro' => $libro->getKey(), 'isbn' => $libro->isbn, 'objetivo' => random_int(1, 20), 'existentes_al_asignar' => $existentes, 'asignado_en' => now()]);
                    $fila = DB::table('inventario_demo_objetivos')->where('id_libro', $libro->getKey())->first();
                }
                if ($fila->isbn !== $libro->isbn || $fila->objetivo < 1 || $fila->objetivo > 20) {
                    throw new RuntimeException('Objetivo persistente inválido o de otra edición.');
                }
                $faltantes = max(0, $fila->objetivo - $existentes);
                $creados = [];
                $secuencia = 1;
                while (count($creados) < $faltantes) {
                    $codigo = 'DEMO-PRUEBA-'.$libro->isbn.'-'.str_pad((string) $secuencia++, 2, '0', STR_PAD_LEFT);
                    $usado = Ejemplar::where('codigo_inventario', $codigo)->first();
                    if ($usado) {
                        if ($usado->id_libro !== $libro->getKey()) {
                            throw new RuntimeException('Código DEMO asociado a otro libro.');
                        }

                        continue;
                    }
                    $creados[] = Ejemplar::create(['codigo_inventario' => $codigo, 'id_libro' => $libro->getKey(), 'id_ubicacion' => $ubicacion->getKey(), 'id_estado_ejemplar' => $estados->first()->getKey()])->getKey();
                }
                $resultado[] = ['id_libro' => $libro->getKey(), 'titulo' => $libro->titulo, 'isbn' => $libro->isbn, 'objetivo' => $fila->objetivo, 'antes' => $existentes, 'creados' => count($creados), 'ids_creados' => $creados, 'despues' => $libro->ejemplares()->count(), 'conservar_mas_de_20' => $existentes > 20, 'sobre_objetivo' => $existentes > $fila->objetivo];
            }

            return $resultado;
        }, 3);
    }
}
