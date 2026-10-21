<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleDevolucion extends Model
{
    protected $table = 'detalle_devolucion';
    protected $primaryKey = 'id_detalle_devolucion';
    public $timestamps = false;
    protected $fillable = ['id_devolucion', 'id_detalle_prestamo', 'observaciones'];

    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(Devolucion::class, 'id_devolucion', 'id_devolucion');
    }

    public function detallePrestamo(): BelongsTo
    {
        return $this->belongsTo(DetallePrestamo::class, 'id_detalle_prestamo', 'id_detalle');
    }
}
