<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuentaPermiso extends Model
{
    protected $table = 'cuenta_permisos';
    protected $primaryKey = 'id_permiso';
    public $timestamps = false;
    protected $fillable = ['id_cuenta', 'modulo', 'accion'];
}
