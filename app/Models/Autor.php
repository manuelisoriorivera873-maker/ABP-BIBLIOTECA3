<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Autor extends Model
{
    protected $table = 'autores';
    protected $primaryKey = 'id_autor';
    public $timestamps = false;

    protected $fillable = ['id_persona'];

    public function ficha()
    {
        return $this->hasOne(AutorFicha::class, 'id_autor', 'id_autor');
    }

    public function getNombrePublicoAttribute(): string
    {
        return $this->ficha?->nombre_publico ?? $this->nombre_completo;
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    public function libros(): BelongsToMany
    {
        return $this->belongsToMany(Libro::class, 'asigna_autores', 'id_autor', 'id_libro');
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->persona?->nombre_completo ?? 'Autor sin persona vinculada';
    }
}
