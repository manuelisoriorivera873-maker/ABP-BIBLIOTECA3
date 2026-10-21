<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UbicacionLibro extends Model
{
    protected $table = 'ubicaciones_libros';
    protected $primaryKey = 'id_ubicacion';
    public $timestamps = false;
    protected $fillable = ['nombre_ubicacion', 'descripcion'];

    public function ejemplares()
    {
        return $this->hasMany(Ejemplar::class, 'id_ubicacion');
    }
}
