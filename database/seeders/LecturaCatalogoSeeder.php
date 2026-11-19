<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Libro;
use App\Models\RecursoDigital;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LecturaCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $datos = json_decode(file_get_contents(database_path('data/lectura-catalogo.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($datos): void {
            $normalizar = fn ($s) => Str::lower(Str::ascii(trim($s)));
            foreach (array_unique(array_merge(...array_column($datos['libros'], 'categorias'))) as $nombre) {
                if (! Categoria::get()->contains(fn ($c) => $normalizar($c->nombre) === $normalizar($nombre))) {
                    Categoria::create(['nombre' => $nombre]);
                }
            }
            (new EnriquecerCatalogoSeeder)->importar($datos);
            foreach ($datos['libros'] as $dato) {
                $libro = Libro::where('isbn', $dato['isbn'])->firstOrFail();
                foreach ($dato['categorias'] as $nombre) {
                    $categoria = Categoria::get()->firstOrFail(fn ($c) => $normalizar($c->nombre) === $normalizar($nombre));
                    $libro->categorias()->syncWithoutDetaching([$categoria->getKey()]);
                }
            }
            foreach ($datos['vinculos_obras_existentes'] as $vinculo) {
                // Solo agrupa ediciones cotejadas; no cambia idiomas, ISBN ni inventario.
                Libro::where('isbn', $vinculo['isbn'])->first()?->ficha()->update(['obra_fuente' => $vinculo['obra_fuente']]);
            }
            foreach ($datos['recursos'] as $dato) {
                $libro = Libro::where('isbn', $dato['isbn'])->firstOrFail();
                unset($dato['isbn']);
                if ($dato['tipo'] === 'integrado') {
                    $ruta = storage_path('app/private/lecturas/'.$dato['ruta_privada']);
                    if (! is_file($ruta) || ! hash_equals($dato['sha256'], hash_file('sha256', $ruta))) {
                        throw new RuntimeException('Falta el recurso digital verificado. No se habilita un enlace roto.');
                    }
                }
                $anterior = RecursoDigital::where('id_libro', $libro->getKey())->where('fuente', $dato['fuente'])->where('formato', $dato['formato'])->first();
                RecursoDigital::updateOrCreate(['id_libro' => $libro->getKey(), 'fuente' => $dato['fuente'], 'formato' => $dato['formato']], $dato + ['publico' => $anterior?->publico ?? (string) Str::uuid(), 'disponible' => $anterior?->disponible ?? true, 'verificado_en' => $anterior?->verificado_en ?? now()]);
            }
        });
    }
}
