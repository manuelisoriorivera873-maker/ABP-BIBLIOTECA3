<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Devolucion extends Model
{
    protected $table = 'devoluciones';
    protected $primaryKey = 'id_devolucion';
    public $timestamps = false;
    protected $fillable = ['id_prestamo', 'fecha_devolucion'];
    protected $casts = ['fecha_devolucion' => 'date'];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class, 'id_prestamo');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleDevolucion::class, 'id_devolucion', 'id_devolucion');
    }
}
