<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetallePrestamo extends Model
{
    protected $table = 'detalle_prestamo';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;
    protected $fillable = ['id_prestamo', 'id_ejemplar'];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class, 'id_prestamo');
    }

    public function ejemplar(): BelongsTo
    {
        return $this->belongsTo(Ejemplar::class, 'id_ejemplar');
    }

    public function devolucionesDetalle(): HasMany
    {
        return $this->hasMany(DetalleDevolucion::class, 'id_detalle_prestamo', 'id_detalle');
    }
}
