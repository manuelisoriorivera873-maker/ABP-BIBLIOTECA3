<?php

use App\Support\PermisosBiblioteca;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rol_permisos') || Schema::hasTable('historial_rol_permisos')) {
            throw new RuntimeException('El esquema RBAC ya existe o está incompleto. Verifica su estado antes de continuar.');
        }
        Schema::create('rol_permisos', function (Blueprint $table): void {
            $table->bigIncrements('id_permiso');
            $table->string('rol', 20);
            $table->string('modulo', 40);
            $table->string('accion', 12);
            $table->unique(['rol', 'modulo', 'accion'], 'uq_rol_modulo_accion');
        });
        Schema::create('historial_rol_permisos', function (Blueprint $table): void {
            $table->bigIncrements('id_cambio');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('rol', 20);
            $table->json('detalle');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('actor_id')->references('id_cuenta')->on('cuentas_acceso')->nullOnDelete();
        });
        DB::transaction(function (): void {
            foreach (['BIBLIOTECARIO', 'USUARIO'] as $rol) {
                foreach (PermisosBiblioteca::iniciales($rol) as $permiso) {
                    [$modulo, $accion] = explode(':', $permiso, 2);
                    DB::table('rol_permisos')->insert(compact('rol', 'modulo', 'accion'));
                }
                DB::table('historial_rol_permisos')->insert([
                    'actor_id' => null, 'rol' => $rol,
                    'detalle' => json_encode(['evento' => 'MATRIZ_INSTITUCIONAL_INICIAL', 'permisos' => PermisosBiblioteca::iniciales($rol), 'origen' => 'Solicitud institucional; no unión de permisos individuales']),
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Se conservan las matrices y su historial. La reversión requiere un procedimiento con respaldo.');
    }
};
