<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ejemplar extends Model
{
    protected $table = 'ejemplares';
    protected $primaryKey = 'id_ejemplar';
    public $timestamps = false;
    protected $fillable = ['codigo_inventario', 'id_libro', 'id_ubicacion', 'id_estado_ejemplar'];

    public function libro()
    {
        return $this->belongsTo(Libro::class, 'id_libro');
    }

    public function ubicacion()
    {
        return $this->belongsTo(UbicacionLibro::class, 'id_ubicacion');
    }

    public function estado()
    {
        return $this->belongsTo(EstadoEjemplar::class, 'id_estado_ejemplar');
    }

    public function detallesPrestamo()
    {
        return $this->hasMany(DetallePrestamo::class, 'id_ejemplar');
    }
}
