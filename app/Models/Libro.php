<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Libro extends Model
{
    protected $table = 'libros';

    protected $primaryKey = 'id_libro';

    public $timestamps = false;

    protected $fillable = ['isbn', 'titulo'];

    protected static function booted(): void
    {
        static::created(function (Libro $libro): void {
            $libro->ficha()->create(['publico' => (string) Str::uuid()]);
        });
        static::updated(function (Libro $libro): void {
            if ($libro->wasChanged(['titulo', 'isbn'])) {
                RecursoDigital::where('id_libro', $libro->getKey())->update(['disponible' => false]);
                // La misma URL pública permanece; otra identidad bibliográfica
                // no puede heredar la verificación ni portada de la anterior.
                $libro->ficha()->update(array_fill_keys([
                    'titulo_verificado', 'isbn_verificado', 'editorial_verificada',
                    'anio', 'sinopsis', 'portada_url', 'fuente_url', 'portada_fuente', 'verificado_en',
                    'idioma', 'revision_bibliografica', 'portada_verificada_en', 'obra_fuente',
                ], null));
            }
        });
    }

    public function recursosDigitales()
    {
        return $this->hasMany(RecursoDigital::class, 'id_libro')->autorizados();
    }

    public function ficha()
    {
        return $this->hasOne(LibroFicha::class, 'id_libro');
    }

    public function getTituloPublicoAttribute(): string
    {
        return $this->ficha?->titulo_verificado ?: $this->titulo;
    }

    public function autores()
    {
        return $this->belongsToMany(Autor::class, 'asigna_autores', 'id_libro', 'id_autor');
    }

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'asigna_categorias', 'id_libro', 'id_categoria');
    }

    public function editoriales()
    {
        return $this->belongsToMany(Editorial::class, 'asigna_editoriales', 'id_libro', 'id_editorial');
    }

    public function ejemplares()
    {
        return $this->hasMany(Ejemplar::class, 'id_libro');
    }
}
