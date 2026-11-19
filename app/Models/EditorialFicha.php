<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EditorialFicha extends Model
{
    protected $table = 'editorial_fichas';

    protected $primaryKey = 'id_editorial';

    public $timestamps = false;

    protected $guarded = [];
}
