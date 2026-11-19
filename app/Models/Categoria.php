<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $primaryKey = 'id_categoria';

    public $timestamps = false;

    protected $fillable = ['nombre'];

    public const EQUIVALENCIAS = ['Literatura infantil' => 'Infantil', 'Cuentos' => 'Cuento'];

    public function getNombrePublicoAttribute(): string
    {
        return self::EQUIVALENCIAS[$this->nombre] ?? $this->nombre;
    }

    public function scopeSinAlias($query)
    {
        // Las etiquetas y sus relaciones se conservan; el lector ve un género una sola vez.
        $ocultas = array_keys(array_filter(self::EQUIVALENCIAS, fn ($nombre) => self::where('nombre', $nombre)->exists()));

        return $query->whereNotIn('nombre', $ocultas);
    }

    public function ficha()
    {
        return $this->hasOne(CategoriaFicha::class, 'id_categoria', 'id_categoria');
    }

    public function libros()
    {
        return $this->belongsToMany(Libro::class, 'asigna_categorias', 'id_categoria', 'id_libro');
    }
}
