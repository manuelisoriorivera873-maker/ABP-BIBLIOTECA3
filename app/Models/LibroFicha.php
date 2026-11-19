<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibroFicha extends Model
{
    protected $table = 'libro_fichas';

    protected $primaryKey = 'id_libro';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['verificado_en' => 'datetime', 'portada_verificada_en' => 'datetime'];

    public function libro()
    {
        return $this->belongsTo(Libro::class, 'id_libro');
    }
}
