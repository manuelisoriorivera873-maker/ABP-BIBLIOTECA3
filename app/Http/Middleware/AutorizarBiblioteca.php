<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutorizarBiblioteca
{
    public function handle(Request $request, Closure $next): Response
    {
        $cuenta = $request->user();
        abort_unless($cuenta && $cuenta->activo, 403, 'Cuenta desactivada o sin autorización.');

        $nombre = $request->route()?->getName();
        if ($nombre === 'dashboard') {
            if ($cuenta->rol === 'USUARIO') {
                return redirect()->route(...$cuenta->destinoInicio());
            }
            return $next($request);
        }

        if (str_starts_with((string) $nombre, 'cuentas.') || str_starts_with((string) $nombre, 'permisos.')) {
            abort_unless($cuenta->rol === 'SUPERADMIN', 403);
            return $next($request);
        }

        if ($nombre === 'devoluciones.registrar') {
            $modulo = 'devoluciones';
            $accion = 'crear';
        } elseif ($nombre === 'rapido.autores') {
            $modulo = 'autores';
            $accion = 'crear';
        } elseif ($nombre === 'rapido.catalogos') {
            $modulo = $request->route('catalogo');
            $accion = 'crear';
        } else {
            $partes = explode('.', (string) $nombre);
            $modulo = $partes[0] === 'catalogos'
                ? $request->route('catalogo')
                : $partes[0];
            $metodo = $partes[1] ?? '';
            $accion = match ($metodo) {
                'index', 'show' => 'ver',
                'create', 'store' => 'crear',
                'edit', 'update' => 'editar',
                'destroy' => 'eliminar',
                default => null,
            };
        }

        abort_unless(
            is_string($modulo) && is_string($accion) && $cuenta->puede($modulo, $accion),
            403,
            'No tienes permiso para realizar esta operación.'
        );

        return $next($request);
    }
}
