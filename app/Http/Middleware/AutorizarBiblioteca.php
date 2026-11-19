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
        abort_unless($cuenta && $cuenta->activo && in_array($cuenta->rol, ['SUPERADMIN', 'BIBLIOTECARIO', 'USUARIO'], true), 403, 'Cuenta desactivada o sin autorización.');

        $nombre = $request->route()?->getName();
        if (str_starts_with((string) $nombre, 'lector.')) {
            abort_unless($cuenta->rol === 'USUARIO', 403);
            $metodo = substr($nombre, strlen('lector.'));
            $modulo = match ($metodo) {
                'bandeja', 'seleccionar', 'enviar', 'solicitudes', 'prestamos' => 'solicitudes',
                'bibliografia' => $request->route('tipo'),
                'autor' => 'autores',
                default => 'libros',
            };
            $accion = in_array($metodo, ['bandeja', 'seleccionar', 'enviar'], true) ? 'crear' : 'ver';
            abort_unless($cuenta->puede($modulo, $accion), 403);
            view()->share('puedeVerAutores', $cuenta->puede('autores', 'ver'));

            return $next($request);
        }
        if ($cuenta->rol === 'USUARIO') {
            $destino = match ($nombre) {
                'dashboard' => 'lector.inicio', 'libros.index' => 'lector.explorar',
                'solicitudes.index' => 'lector.solicitudes', 'solicitudes.create' => 'lector.bandeja',
                'prestamos.index', 'solicitudes.prestamos', 'devoluciones.index' => 'lector.prestamos',
                default => null,
            };
            if ($destino) {
                return redirect()->route($destino, $nombre === 'libros.index' ? $request->only('buscar') : []);
            }
            if ($nombre === 'autores.index') {
                return redirect()->route('lector.bibliografia', ['tipo' => 'autores']);
            }
            if ($nombre === 'catalogos.index' && in_array($request->route('catalogo'), ['categorias', 'editoriales'], true)) {
                return redirect()->route('lector.bibliografia', ['tipo' => $request->route('catalogo')]);
            }
            // Un lector no entra a herramientas administrativas aun si conserva
            // casillas históricas. La solicitud propia conserva su endpoint anterior.
            abort_unless($nombre === 'solicitudes.store', 403);
        }
        if ($nombre === 'dashboard') {
            if ($cuenta->rol === 'USUARIO') {
                return redirect()->route('lector.inicio');
            }

            return $next($request);
        }

        if (str_starts_with((string) $nombre, 'cuentas.') || str_starts_with((string) $nombre, 'permisos.')) {
            abort_unless($cuenta->rol === 'SUPERADMIN', 403);

            return $next($request);
        }

        if ($nombre === 'solicitudes.prestamos') {
            abort_unless($cuenta->rol === 'USUARIO', 403);
            $modulo = 'solicitudes';
            $accion = 'ver';
        } elseif ($nombre === 'solicitudes.disponibilidad') {
            $modulo = 'solicitudes';
            $accion = 'editar';
        } elseif ($nombre === 'devoluciones.registrar') {
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
