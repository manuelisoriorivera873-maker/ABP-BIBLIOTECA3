<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_acceso', function (Blueprint $table): void {
            $table->bigIncrements('id_cuenta');
            $table->string('nombre_usuario', 60)->unique();
            $table->string('password');
            $table->string('rol', 20);
            $table->boolean('activo')->default(true);
            $table->integer('id_usuario')->nullable()->unique();
            $table->integer('id_bibliotecario')->nullable()->unique();
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamps();
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_bibliotecario')->references('id_bibliotecario')->on('bibliotecarios')->restrictOnDelete()->cascadeOnUpdate();
            $table->index(['rol', 'activo']);
        });

        Schema::create('cuenta_permisos', function (Blueprint $table): void {
            $table->bigIncrements('id_permiso');
            $table->unsignedBigInteger('id_cuenta');
            $table->string('modulo', 40);
            $table->string('accion', 12);
            $table->foreign('id_cuenta')->references('id_cuenta')->on('cuentas_acceso')->cascadeOnDelete();
            $table->unique(['id_cuenta', 'modulo', 'accion'], 'uq_cuenta_modulo_accion');
        });

        Schema::create('historial_permisos', function (Blueprint $table): void {
            $table->bigIncrements('id_cambio');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedBigInteger('cuenta_id');
            $table->string('evento', 40);
            $table->json('detalle')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('actor_id')->references('id_cuenta')->on('cuentas_acceso')->nullOnDelete();
            $table->foreign('cuenta_id')->references('id_cuenta')->on('cuentas_acceso')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Los datos de seguridad son auditables; use rollback solo en el laboratorio.
        Schema::dropIfExists('historial_permisos');
        Schema::dropIfExists('cuenta_permisos');
        Schema::dropIfExists('cuentas_acceso');
    }
};
