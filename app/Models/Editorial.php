<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Editorial extends Model
{
    protected $table = 'editoriales';
    protected $primaryKey = 'id_editorial';
    public $timestamps = false;
    protected $fillable = ['nombre'];

    public function libros()
    {
        return $this->belongsToMany(Libro::class, 'asigna_editoriales', 'id_editorial', 'id_libro');
    }
}
