<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudDetalle extends Model
{
    protected $table = 'solicitud_detalles';

    protected $primaryKey = 'id_detalle_solicitud';

    public $timestamps = false;

    protected $guarded = ['id_detalle_solicitud'];

    public function libro()
    {
        return $this->belongsTo(Libro::class, 'id_libro');
    }
}
