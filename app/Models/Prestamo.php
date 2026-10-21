<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prestamo extends Model
{
    protected $table = 'prestamos';
    protected $primaryKey = 'id_prestamo';
    public $timestamps = false;
    protected $fillable = ['id_usuario', 'fecha_prestamo', 'fecha_limite', 'id_bibliotecario'];
    protected $casts = ['fecha_prestamo' => 'date', 'fecha_limite' => 'date'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function bibliotecario(): BelongsTo
    {
        return $this->belongsTo(Bibliotecario::class, 'id_bibliotecario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePrestamo::class, 'id_prestamo');
    }

    public function devoluciones(): HasMany
    {
        return $this->hasMany(Devolucion::class, 'id_prestamo', 'id_prestamo');
    }

    public function devolucion(): HasOne
    {
        return $this->hasOne(Devolucion::class, 'id_prestamo', 'id_prestamo')->latestOfMany('id_devolucion');
    }
}
