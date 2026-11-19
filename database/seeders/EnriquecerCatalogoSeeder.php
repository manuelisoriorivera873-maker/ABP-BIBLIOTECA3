<?php

namespace Database\Seeders;

use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Editorial;
use App\Models\Libro;
use App\Models\Persona;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EnriquecerCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $datos = json_decode(file_get_contents(database_path('data/enriquecimiento.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->importar($datos);
    }

    public function importar(array $datos): void
    {
        $normalizar = fn ($s) => Str::lower(Str::ascii(trim((string) $s)));
        DB::transaction(function () use ($datos, $normalizar): void {
            $autores = Autor::with('ficha')->get()->filter(fn ($a) => $a->ficha?->nombre_publico)->keyBy(fn ($a) => $a->ficha->nombre_publico)->all();
            foreach ($datos['autores'] as $dato) {
                // La ficha bibliográfica no modifica los datos civiles de personas.
                $nombre = $normalizar($dato['nombre_publico']);
                $coincidencias = Persona::get()->filter(fn ($p) => $normalizar($p->nombre_completo) === $nombre || str_starts_with($normalizar($p->nombre_completo), $nombre.' '));
                if ($coincidencias->count() > 1) {
                    throw new RuntimeException('Persona ambigua: '.$dato['nombre_publico']);
                }
                $dato['apellido_materno'] ??= '';
                $registrado = Autor::with(['ficha', 'persona'])->get()->filter(fn ($a) => $normalizar($a->ficha?->nombre_publico) === $nombre);
                if ($registrado->count() > 1) {
                    throw new RuntimeException('Autor bibliográfico ambiguo: '.$dato['nombre_publico']);
                }
                $persona = $registrado->first()?->persona ?? $coincidencias->first() ?? Persona::create(collect($dato)->only(['nombre', 'apellido_paterno', 'apellido_materno'])->all());
                $autor = Autor::firstOrCreate(['id_persona' => $persona->getKey()]);
                $ficha = collect($dato)->only(['nombre_publico', 'nacionalidad', 'nacimiento', 'fallecimiento', 'biografia', 'fuente_url'])->all();
                $autor->ficha()->updateOrCreate([], $ficha + ['publico' => $autor->ficha?->publico ?? (string) Str::uuid()]);
                $autores[$dato['nombre_publico']] = $autor;
            }
            foreach ($datos['libros'] as $dato) {
                $isbn = $dato['isbn'];
                $suma = 0;
                for ($i = 0; $i < 13; $i++) {
                    $suma += (int) ($isbn[$i] ?? 0) * ($i % 2 ? 3 : 1);
                }
                if (! preg_match('/^97[89]\d{10}$/', $isbn) || $suma % 10 !== 0 || count(preg_split('/\s+/u', trim($dato['sinopsis']))) < 50) {
                    throw new RuntimeException('Ficha inválida: '.$dato['titulo']);
                }
                $coincidencias = Libro::get()->filter(fn ($l) => preg_replace('/\D/', '', $l->isbn) === $isbn);
                if ($coincidencias->isEmpty() && empty($dato['nueva_edicion']) && Libro::get()->contains(fn ($l) => $normalizar($l->titulo) === $normalizar($dato['titulo']))) {
                    throw new RuntimeException('Otra edición requiere cotejo explícito: '.$dato['titulo']);
                }
                if ($coincidencias->count() > 1) {
                    throw new RuntimeException('Título o ISBN duplicado: '.$dato['titulo']);
                }
                $libro = $coincidencias->first();
                if ($libro && ($normalizar($libro->titulo) !== $normalizar($dato['titulo']) || preg_replace('/\D/', '', $libro->isbn) !== $isbn)) {
                    throw new RuntimeException('Edición en conflicto; requiere cotejo: '.$dato['titulo']);
                }
                if (! $libro) {
                    $libro = Libro::create(['titulo' => $dato['titulo'], 'isbn' => $isbn]);
                    $libro->autores()->syncWithoutDetaching(array_map(fn ($nombre) => $autores[$nombre]->getKey(), $dato['autores'] ?? [$dato['autor']]));
                    foreach ($dato['categorias'] ?? [$dato['categoria']] as $nombreCategoria) {
                        $categoria = Categoria::get()->first(fn ($c) => $normalizar($c->nombre) === $normalizar($nombreCategoria)) ?? Categoria::create(['nombre' => $nombreCategoria]);
                        $libro->categorias()->syncWithoutDetaching([$categoria->getKey()]);
                    }
                    $libro->editoriales()->syncWithoutDetaching([Editorial::firstOrCreate(['nombre' => $dato['editorial']])->getKey()]);
                }
                $libro->ficha()->firstOrCreate([], ['publico' => (string) Str::uuid()]);
                $libro->ficha()->update([
                    'titulo_verificado' => $dato['titulo'], 'isbn_verificado' => $isbn, 'editorial_verificada' => $dato['editorial'],
                    'anio' => $dato['anio'], 'sinopsis' => $dato['sinopsis'], 'portada_url' => $dato['portada'],
                    'fuente_url' => $dato['fuente'], 'portada_fuente' => $dato['portada_fuente'],
                    'verificado_en' => $libro->ficha?->verificado_en ?? now(),
                ] + collect($dato)->only(['idioma', 'revision_bibliografica', 'portada_verificada_en', 'obra_fuente'])->all());
            }
            foreach (Categoria::all() as $categoria) {
                if (($descripcion = $datos['categorias'][$categoria->nombre] ?? null) && (empty($datos['preservar_fichas_existentes']) || ! $categoria->ficha()->exists())) {
                    $categoria->ficha()->updateOrCreate([], ['descripcion' => $descripcion]);
                }
            }
            foreach (Editorial::with('libros.ficha')->get() as $editorial) {
                $dato = $datos['editoriales'][$editorial->nombre] ?? null;
                if ($dato) {
                    $editorial->ficha()->updateOrCreate([], $dato);
                } elseif (! $editorial->ficha()->exists() && ($libro = $editorial->libros->first(fn ($l) => $l->ficha?->editorial_verificada === $editorial->nombre))) {
                    $editorial->ficha()->updateOrCreate([], ['descripcion' => 'Entre las obras publicadas por esta editorial se encuentra «'.$libro->titulo_publico.'». Explora aquí los títulos de su catálogo registrados en la biblioteca.', 'fuente_url' => $libro->ficha->fuente_url]);
                }
            }
            // No se crean ejemplares, cuentas, préstamos ni relaciones con lectores.
        });
    }
}
