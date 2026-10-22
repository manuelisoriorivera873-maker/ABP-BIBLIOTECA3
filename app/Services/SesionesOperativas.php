<?php

namespace App\Services;

use App\Models\CuentaAcceso;
use App\Models\SolicitudPrestamo;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Session\EncryptedStore;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SesionesOperativas
{
    public function actualizar(Request $request, ?bool $disponible = null): bool
    {
        $huella = hash('sha256', $request->session()->getId());
        $fila = DB::table('sesiones_operativas')->where('huella', $huella)->first();
        $activo = $disponible ?? (bool) ($fila?->disponible ?? false);
        DB::table('sesiones_operativas')->updateOrInsert(['huella' => $huella], [
            'sesion_cifrada' => Crypt::encryptString($request->session()->getId()),
            'id_cuenta' => $request->user()->getKey(), 'disponible' => $activo, 'actividad' => now(),
        ]);

        return $activo;
    }

    public function disponibles(): array
    {
        $cuentas = [];
        foreach (DB::table('sesiones_operativas')->where('disponible', true)
            ->where('actividad', '>=', now()->subMinutes(config('prestamos.sesion_operativa_minutos')))->get() as $fila) {
            $cuenta = CuentaAcceso::find($fila->id_cuenta);
            if (! $cuenta || $cuenta->rol !== 'BIBLIOTECARIO' || ! $cuenta->id_bibliotecario
                || ! $cuenta->puede('solicitudes', 'ver') || ! $cuenta->puede('solicitudes', 'editar')) {
                continue;
            }
            try {
                // Leer el almacenamiento real de sesiones, no ultimo_acceso.
                // Una sesión cerrada, borrada o expirada no acredita disponibilidad.
                $id = Crypt::decryptString($fila->sesion_cifrada);
                $handler = app('session')->driver()->getHandler();
                $sesion = config('session.encrypt')
                    ? new EncryptedStore('operativa', $handler, app('encrypter'), $id, config('session.serialization', 'php'))
                    : new Store('operativa', $handler, $id, config('session.serialization', 'php'));
                $sesion->start();
                if ((int) $sesion->get(Auth::guard()->getName()) === (int) $cuenta->getKey()) {
                    $cuentas[$cuenta->getKey()] = $cuenta;
                }
            } catch (DecryptException) {
                continue;
            }
        }

        return $cuentas;
    }

    public function asignarPendientes(): void
    {
        DB::transaction(function (): void {
            $disponibles = $this->disponibles();
            if ($disponibles === []) {
                return;
            }
            // Orden estable y bloqueo: dos despachadores no asignan la misma fila.
            $pendientes = SolicitudPrestamo::where('estado', 'PENDIENTE')
                ->orderBy('id_solicitud')->lockForUpdate()->get();
            $cargas = [];
            foreach ($disponibles as $id => $cuenta) {
                $cargas[$id] = $pendientes->where('asignada_a', $id)->count();
            }
            foreach ($pendientes as $solicitud) {
                if ($solicitud->asignada_a && isset($disponibles[$solicitud->asignada_a])) {
                    continue;
                }
                asort($cargas);
                $id = array_key_first($cargas);
                $solicitud->update(['asignada_a' => $id]);
                $cargas[$id]++;
            }
        }, 3);
    }
}
