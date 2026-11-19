<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaFicha extends Model
{
    protected $table = 'categoria_fichas';

    protected $primaryKey = 'id_categoria';

    public $timestamps = false;

    protected $guarded = [];
}
