<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoEjemplar extends Model
{
    protected $table = 'estados_ejemplar';
    protected $primaryKey = 'id_estado_ejemplar';
    public $timestamps = false;
    protected $fillable = ['nombre_estado'];

    public function ejemplares()
    {
        return $this->hasMany(Ejemplar::class, 'id_estado_ejemplar');
    }
}
