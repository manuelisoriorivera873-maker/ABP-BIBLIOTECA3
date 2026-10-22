<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_prestamo', function (Blueprint $table): void {
            $table->bigIncrements('id_solicitud');
            $table->integer('id_usuario');
            $table->unsignedBigInteger('asignada_a')->nullable();
            $table->unsignedBigInteger('atendida_por')->nullable();
            $table->integer('id_prestamo')->nullable()->unique();
            $table->string('estado', 20)->default('PENDIENTE');
            $table->string('motivo_rechazo', 500)->nullable();
            $table->timestamp('atendida_en')->nullable();
            $table->timestamps();
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            $table->foreign('asignada_a')->references('id_cuenta')->on('cuentas_acceso');
            $table->foreign('atendida_por')->references('id_cuenta')->on('cuentas_acceso');
            $table->foreign('id_prestamo')->references('id_prestamo')->on('prestamos');
            $table->index(['estado', 'asignada_a']);
        });
        Schema::create('solicitud_detalles', function (Blueprint $table): void {
            $table->bigIncrements('id_detalle_solicitud');
            $table->unsignedBigInteger('id_solicitud');
            $table->integer('id_libro');
            $table->unsignedTinyInteger('cantidad');
            $table->foreign('id_solicitud')->references('id_solicitud')->on('solicitudes_prestamo');
            $table->foreign('id_libro')->references('id_libro')->on('libros');
            $table->unique(['id_solicitud', 'id_libro']);
        });
        Schema::create('sesiones_operativas', function (Blueprint $table): void {
            $table->string('huella', 64)->primary();
            $table->text('sesion_cifrada');
            $table->unsignedBigInteger('id_cuenta');
            $table->boolean('disponible')->default(false);
            $table->timestamp('actividad');
            $table->foreign('id_cuenta')->references('id_cuenta')->on('cuentas_acceso');
            $table->index(['disponible', 'actividad']);
        });
        DB::transaction(function (): void {
            foreach (['USUARIO' => ['ver', 'crear'], 'BIBLIOTECARIO' => ['ver', 'editar']] as $rol => $acciones) {
                foreach ($acciones as $accion) {
                    DB::table('rol_permisos')->insertOrIgnore(['rol' => $rol, 'modulo' => 'solicitudes', 'accion' => $accion]);
                }
                DB::table('historial_rol_permisos')->insert([
                    'actor_id' => null, 'rol' => $rol,
                    'detalle' => json_encode(['evento' => 'HABILITACION_SOLICITUDES', 'acciones' => $acciones]),
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Se conservan las solicitudes y su historial. Requiere un procedimiento con respaldo.');
    }
};
