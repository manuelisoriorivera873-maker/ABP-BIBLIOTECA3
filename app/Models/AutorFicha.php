<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutorFicha extends Model
{
    protected $table = 'autor_fichas';

    protected $primaryKey = 'id_autor';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['nacimiento' => 'date', 'fallecimiento' => 'date'];
}
