<?php

namespace Database\Seeders;

use App\Models\Libro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CompletarCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $datos = json_decode(file_get_contents(database_path('data/ampliacion.json')), true, 512, JSON_THROW_ON_ERROR);
        $revisiones = json_decode(file_get_contents(database_path('data/revision-catalogo.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($datos, $revisiones): void {
            $existentes = Libro::with('ficha')->get()->keyBy('isbn');
            foreach ($datos['libros'] as &$dato) {
                $dato['portada_verificada_en'] = $existentes->get($dato['isbn'])?->ficha?->portada_verificada_en ?? now()->startOfSecond();
            }
            unset($dato);
            // Reutilizar el importador: no hay una segunda matriz ni otro catálogo.
            (new EnriquecerCatalogoSeeder)->importar($datos);
            foreach ($revisiones as $revision) {
                $libro = Libro::where('titulo', $revision['titulo'])->where('isbn', $revision['isbn_esperado'])->first();
                if (! $libro) {
                    // No identificar una obra por una clave numérica de otro entorno.
                    continue;
                }
                $normalizar = fn ($n) => Str::lower(Str::ascii(trim($n)));
                $autores = $libro->autores()->with('persona')->get()->map(fn ($a) => $normalizar($a->nombre_completo))->sort()->values()->all();
                $esperados = collect($revision['autores_esperados'])->map($normalizar)->sort()->values()->all();
                if ($autores !== $esperados) {
                    throw new RuntimeException('La autoría cambió desde la revisión: '.$revision['titulo']);
                }
                $permitidos = ['sinopsis', 'revision_bibliografica', 'portada_url', 'portada_fuente', 'idioma'];
                if (array_diff(array_keys($revision['campos']), $permitidos)) {
                    throw new RuntimeException('La revisión intenta modificar campos no autorizados.');
                }
                $campos = $revision['campos'];
                if ($revision['portada_verificada'] ?? false) {
                    $campos['portada_verificada_en'] = $libro->ficha?->portada_verificada_en ?? now()->startOfSecond();
                }
                $libro->ficha()->update($campos);
            }
        });
    }
}
