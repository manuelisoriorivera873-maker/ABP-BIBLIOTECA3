<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bibliotecario extends Model
{
    protected $table = 'bibliotecarios';
    protected $primaryKey = 'id_bibliotecario';
    public $timestamps = false;
    protected $fillable = ['id_persona'];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class, 'id_bibliotecario');
    }
    public function cuentaAcceso()
    {
        return $this->hasOne(\App\Models\CuentaAcceso::class, 'id_bibliotecario', 'id_bibliotecario');
    }
}
