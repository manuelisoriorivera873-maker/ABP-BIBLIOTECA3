<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class PermisosBiblioteca
{
    public const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    public const MODULOS = [
        'libros' => 'Libros',
        'autores' => 'Autores',
        'ejemplares' => 'Ejemplares',
        'personas' => 'Personas',
        'usuarios' => 'Usuarios (lectores)',
        'bibliotecarios' => 'Bibliotecarios (personal)',
        'prestamos' => 'Préstamos',
        'solicitudes' => 'Solicitudes de préstamo',
        'devoluciones' => 'Devoluciones',
        'categorias' => 'Categorías',
        'editoriales' => 'Editoriales',
        'ubicaciones' => 'Ubicaciones',
        'estados-ejemplar' => 'Estados de ejemplar',
        'estados-usuario' => 'Estados de usuario',
    ];

    public const LECTURA_USUARIO = ['libros', 'autores', 'categorias', 'editoriales'];

    public static function permiteRol(string $rol, string $modulo, string $accion): bool
    {
        if (! array_key_exists($modulo, self::MODULOS) || ! in_array($accion, self::ACCIONES, true)) {
            return false;
        }

        if ($rol === 'SUPERADMIN') {
            return true;
        }

        // La matriz configura permisos ordinarios para ambos roles. El catálogo
        // de módulos excluye cuentas, roles y privilegios administrativos.
        return in_array($rol, ['BIBLIOTECARIO', 'USUARIO'], true);
    }

    public static function iniciales(string $rol): array
    {
        $resultado = [];
        if ($rol === 'USUARIO') {
            return array_map(fn ($modulo) => $modulo.':ver', self::LECTURA_USUARIO);
        }
        if ($rol !== 'BIBLIOTECARIO') {
            return [];
        }
        foreach (array_keys(self::MODULOS) as $modulo) {
            $acciones = match ($modulo) {
                // Se habilita mediante su migración incremental, sin alterar las matrices existentes.
                'solicitudes' => [],
                'bibliotecarios', 'estados-ejemplar', 'estados-usuario' => ['ver'],
                'devoluciones' => ['ver', 'crear'],
                default => ['ver', 'crear', 'editar'],
            };
            foreach ($acciones as $accion) {
                $resultado[] = $modulo.':'.$accion;
            }
        }

        return $resultado;
    }

    public static function vigentes(string $rol): array
    {
        return DB::table('rol_permisos')->where('rol', $rol)->orderBy('id_permiso')
            ->get(['modulo', 'accion'])
            ->filter(fn ($permiso) => self::permiteRol($rol, $permiso->modulo, $permiso->accion))
            ->map(fn ($permiso) => $permiso->modulo.':'.$permiso->accion)->values()->all();
    }

    public static function concede(string $rol, string $modulo, string $accion): bool
    {
        // Sin caché de sesión ni fallback a permisos individuales heredados.
        return DB::table('rol_permisos')->where(compact('rol', 'modulo', 'accion'))->exists();
    }
}
