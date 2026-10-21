<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    protected $table = 'personas';
    protected $primaryKey = 'id_persona';
    public $timestamps = false;
    protected $fillable = ['nombre', 'apellido_paterno', 'apellido_materno'];

    public function usuario(): HasOne
    {
        return $this->hasOne(Usuario::class, 'id_persona');
    }

    public function bibliotecario(): HasOne
    {
        return $this->hasOne(Bibliotecario::class, 'id_persona');
    }

    public function autor(): HasOne
    {
        return $this->hasOne(Autor::class, 'id_persona', 'id_persona');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido_paterno} {$this->apellido_materno}");
    }
}
