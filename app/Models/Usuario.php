<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;
    protected $fillable = ['telefono', 'id_persona', 'id_estado_usuario'];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function estado()
    {
        return $this->belongsTo(EstadoUsuario::class, 'id_estado_usuario');
    }

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class, 'id_usuario');
    }
    public function cuentaAcceso()
    {
        return $this->hasOne(\App\Models\CuentaAcceso::class, 'id_usuario', 'id_usuario');
    }
}
