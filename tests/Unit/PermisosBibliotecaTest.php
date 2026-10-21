<?php

namespace Tests\Unit;

use App\Support\PermisosBiblioteca;
use PHPUnit\Framework\TestCase;

class PermisosBibliotecaTest extends TestCase
{
    public function test_usuario_es_solo_consulta_de_catalogos_autorizados(): void
    {
        foreach (PermisosBiblioteca::MODULOS as $modulo => $nombre) {
            foreach (PermisosBiblioteca::ACCIONES as $accion) {
                $esperado = $accion === 'ver' && in_array($modulo, PermisosBiblioteca::LECTURA_USUARIO, true);
                $this->assertSame($esperado, PermisosBiblioteca::permiteRol('USUARIO', $modulo, $accion), "$modulo/$accion");
            }
        }
    }

    public function test_superadmin_puede_todo_y_no_hay_privilegios_fuera_de_modulos(): void
    {
        $this->assertTrue(PermisosBiblioteca::permiteRol('SUPERADMIN', 'prestamos', 'eliminar'));
        $this->assertFalse(PermisosBiblioteca::permiteRol('SUPERADMIN', 'cuentas_acceso', 'eliminar'));
    }
}
