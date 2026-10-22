<?php

namespace App\Services;

use App\Models\DetallePrestamo;
use App\Models\Ejemplar;
use App\Models\SolicitudPrestamo;
use App\Models\Usuario;
use Illuminate\Validation\ValidationException;

class SolicitarPrestamo
{
    public function disponibles()
    {
        return Ejemplar::whereHas('estado', fn ($q) => $q->where('nombre_estado', 'DISPONIBLE'))
            ->whereDoesntHave('detallesPrestamo', fn ($q) => $q->whereDoesntHave('devolucionesDetalle'));
    }

    /** Debe ejecutarse dentro de una transacción; serializa las solicitudes del lector. */
    public function validar(int $idUsuario, array $cantidades, ?int $excluir = null): array
    {
        $usuario = Usuario::whereKey($idUsuario)->lockForUpdate()->first();
        if (! $usuario || $usuario->estado?->nombre_estado_usuario !== 'ACTIVO') {
            $this->error('El lector debe estar ACTIVO para solicitar o recibir libros.');
        }
        $total = array_sum($cantidades);
        $maximo = config('prestamos.maximo_ejemplares');
        $porTitulo = config('prestamos.maximo_por_titulo');
        if ($total < 1 || $total > $maximo || max($cantidades ?: [0]) > $porTitulo) {
            $this->error("Selecciona de 1 a {$maximo} ejemplares y como máximo {$porTitulo} por título.");
        }
        $activos = DetallePrestamo::with(['prestamo', 'ejemplar'])->whereHas('prestamo', fn ($q) => $q->where('id_usuario', $idUsuario))
            ->whereDoesntHave('devolucionesDetalle')->get();
        if ($usuario->prestamos()->whereDate('fecha_limite', '<', today())
            ->where(fn ($q) => $q->whereDoesntHave('detalles')->orWhereHas('detalles', fn ($d) => $d->whereDoesntHave('devolucionesDetalle')))->exists()) {
            $this->error('Tienes préstamos vencidos pendientes de devolución.');
        }
        $otras = SolicitudPrestamo::with('detalles')->where('id_usuario', $idUsuario)
            ->whereIn('estado', ['PENDIENTE', 'APROBADA'])->when($excluir, fn ($q) => $q->where('id_solicitud', '!=', $excluir))->get();
        $ocupados = $activos->groupBy('ejemplar.id_libro')->map->count()->all();
        foreach ($otras->flatMap->detalles as $detalle) {
            $ocupados[$detalle->id_libro] = ($ocupados[$detalle->id_libro] ?? 0) + $detalle->cantidad;
        }
        if (array_sum($ocupados) + $total > $maximo) {
            $this->error("Tus préstamos activos y solicitudes abiertas no pueden superar {$maximo} ejemplares.");
        }
        foreach ($cantidades as $libro => $cantidad) {
            if ($cantidad < 1 || ($ocupados[$libro] ?? 0) + $cantidad > $porTitulo) {
                $this->error("No puedes acumular más de {$porTitulo} ejemplares del mismo título.");
            }
        }
        // Bloquear en orden evita seleccionar simultáneamente los mismos ejemplares.
        $seleccion = [];
        ksort($cantidades);
        foreach ($cantidades as $libro => $cantidad) {
            $ejemplares = $this->disponibles()->where('id_libro', $libro)->orderBy('id_ejemplar')->lockForUpdate()->limit($cantidad)->get();
            if ($ejemplares->count() !== $cantidad) {
                $this->error('No hay disponibilidad suficiente para uno de los títulos seleccionados.');
            }
            array_push($seleccion, ...$ejemplares->modelKeys());
        }

        return $seleccion;
    }

    private function error(string $mensaje): never
    {
        throw ValidationException::withMessages(['libros' => $mensaje]);
    }
}
