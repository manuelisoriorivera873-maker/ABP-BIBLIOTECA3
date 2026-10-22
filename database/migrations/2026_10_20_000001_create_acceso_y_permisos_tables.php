<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La instalación histórica creó estas tablas manualmente. No repetir el DDL.
        $existentes = [
            'cuentas_acceso' => ['id_cuenta', 'nombre_usuario', 'password', 'rol', 'activo', 'id_usuario', 'id_bibliotecario', 'ultimo_acceso', 'created_at', 'updated_at'],
            'cuenta_permisos' => ['id_permiso', 'id_cuenta', 'modulo', 'accion'],
            'historial_permisos' => ['id_cambio', 'actor_id', 'cuenta_id', 'evento', 'detalle', 'created_at'],
        ];
        $presentes = array_filter(array_keys($existentes), fn ($tabla) => Schema::hasTable($tabla));
        if ($presentes !== []) {
            if (count($presentes) !== count($existentes)) {
                throw new RuntimeException('Esquema de acceso parcial. Verifica el respaldo y completa la auditoría antes de migrar.');
            }
            foreach ($existentes as $tabla => $columnas) {
                if (! Schema::hasColumns($tabla, $columnas)) {
                    throw new RuntimeException("La tabla {$tabla} no es compatible. Se requiere revisión del esquema; no se alteraron datos.");
                }
            }

            return;
        }

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
        throw new RuntimeException('La migración conserva cuentas e historial. La reversión requiere un procedimiento explícito con respaldo.');
    }
};
