<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Libro extends Model
{
    protected $table = 'libros';
    protected $primaryKey = 'id_libro';
    public $timestamps = false;
    protected $fillable = ['isbn', 'titulo'];

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
