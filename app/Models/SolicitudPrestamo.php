<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudPrestamo extends Model
{
    protected $table = 'solicitudes_prestamo';

    protected $primaryKey = 'id_solicitud';

    protected $guarded = ['id_solicitud'];

    public function detalles()
    {
        return $this->hasMany(SolicitudDetalle::class, 'id_solicitud');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function responsable()
    {
        return $this->belongsTo(CuentaAcceso::class, 'asignada_a');
    }
}
