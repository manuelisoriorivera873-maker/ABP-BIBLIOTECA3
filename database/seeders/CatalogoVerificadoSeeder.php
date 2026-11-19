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

class CatalogoVerificadoSeeder extends Seeder
{
    public function run(): void
    {
        $datos = require database_path('data/bibliografia.php');
        DB::transaction(function () use ($datos): void {
            // Solo fichas complementarias: no se reescribe ningún registro histórico.
            Libro::with('ficha')->orderBy('id_libro')->chunk(100, function ($libros) use ($datos): void {
                foreach ($libros as $libro) {
                    $libro->ficha()->firstOrCreate([], ['publico' => (string) Str::uuid()]);
                    if ($libro->ficha?->verificado_en) {
                        continue;
                    }
                    foreach ($datos['obras'] as $obra) {
                        if (Str::lower(Str::ascii($libro->titulo)) === Str::lower(Str::ascii($obra['titulo']))) {
                            $libro->ficha()->update(['titulo_verificado' => $obra['titulo'], 'fuente_url' => $obra['fuente']]);
                        }
                    }
                }
            });
            foreach ($datos['nuevos'] as $dato) {
                $existente = Libro::where('isbn', $dato['isbn'])->first();
                if ($existente && Str::lower(Str::ascii($existente->titulo)) !== Str::lower(Str::ascii($dato['titulo']))) {
                    continue; // Un ISBN en conflicto requiere revisión, no otra atribución automática.
                }
                if (! $existente && Libro::where('titulo', $dato['titulo'])->exists()) {
                    continue; // No agregar otra edición sin revisar la existente.
                }
                $libro = $existente;
                if (! $libro) {
                    $persona = Persona::firstOrCreate(['nombre' => $dato['nombre'], 'apellido_paterno' => $dato['apellido_paterno'], 'apellido_materno' => $dato['apellido_materno']]);
                    $autor = Autor::firstOrCreate(['id_persona' => $persona->getKey()]);
                    $editorial = Editorial::firstOrCreate(['nombre' => $dato['editorial']]);
                    $categoria = Categoria::firstOrCreate(['nombre' => $dato['categoria']]);
                    $libro = Libro::create(['titulo' => $dato['titulo'], 'isbn' => $dato['isbn']]);
                    $libro->autores()->attach($autor->getKey());
                    $libro->editoriales()->attach($editorial->getKey());
                    $libro->categorias()->attach($categoria->getKey());
                }
                $libro->ficha()->update([
                    'titulo_verificado' => $dato['titulo'], 'isbn_verificado' => $dato['isbn'], 'editorial_verificada' => $dato['editorial'],
                    'anio' => $dato['anio'], 'sinopsis' => $dato['sinopsis'], 'portada_url' => $dato['portada'],
                    'fuente_url' => $dato['fuente'], 'portada_fuente' => 'https://openlibrary.org/isbn/'.$dato['isbn'], 'verificado_en' => $libro->ficha?->verificado_en ?? now(),
                ]);
                // Nunca se crean ejemplares: la biblioteca debe registrar su inventario físico.
            }
        });
    }
}
