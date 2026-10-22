<?php

namespace Tests\Unit;

use App\Support\PermisosBiblioteca;
use PHPUnit\Framework\TestCase;

class PermisosBibliotecaTest extends TestCase
{
    public function test_usuario_admite_permisos_ordinarios_y_conserva_consultas_iniciales(): void
    {
        foreach (PermisosBiblioteca::MODULOS as $modulo => $nombre) {
            foreach (PermisosBiblioteca::ACCIONES as $accion) {
                $this->assertTrue(PermisosBiblioteca::permiteRol('USUARIO', $modulo, $accion), "$modulo/$accion");
            }
        }
        $this->assertSame(['libros:ver', 'autores:ver', 'categorias:ver', 'editoriales:ver'], PermisosBiblioteca::iniciales('USUARIO'));
        $this->assertFalse(PermisosBiblioteca::permiteRol('USUARIO', 'cuentas', 'editar'));
    }

    public function test_superadmin_puede_todo_y_no_hay_privilegios_fuera_de_modulos(): void
    {
        $this->assertTrue(PermisosBiblioteca::permiteRol('SUPERADMIN', 'prestamos', 'eliminar'));
        $this->assertFalse(PermisosBiblioteca::permiteRol('SUPERADMIN', 'cuentas_acceso', 'eliminar'));
    }
}
