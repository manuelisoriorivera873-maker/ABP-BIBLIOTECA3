<?php

namespace App\Support;

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
        if (!array_key_exists($modulo, self::MODULOS) || !in_array($accion, self::ACCIONES, true)) {
            return false;
        }

        if ($rol === 'SUPERADMIN') {
            return true;
        }

        if ($rol === 'USUARIO') {
            return $accion === 'ver' && in_array($modulo, self::LECTURA_USUARIO, true);
        }

        return $rol === 'BIBLIOTECARIO';
    }

    public static function iniciales(string $rol): array
    {
        $modulos = $rol === 'USUARIO'
            ? self::LECTURA_USUARIO
            : ['libros', 'autores', 'ejemplares', 'personas', 'usuarios', 'prestamos', 'devoluciones', 'categorias', 'editoriales', 'ubicaciones', 'estados-ejemplar', 'estados-usuario'];
        $resultado = [];
        foreach ($modulos as $modulo) {
            $resultado[] = $modulo . ':ver';
        }
        return $resultado;
    }
}
