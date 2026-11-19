<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_demo_objetivos', function (Blueprint $t) {
            $t->integer('id_libro')->primary();
            $t->foreign('id_libro')->references('id_libro')->on('libros');
            $t->string('isbn', 20);
            $t->unsignedTinyInteger('objetivo');
            $t->unsignedInteger('existentes_al_asignar');
            $t->timestamp('asignado_en');
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE inventario_demo_objetivos ADD CONSTRAINT chk_demo_objetivo CHECK (objetivo BETWEEN 1 AND 20)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_demo_objetivos');
    }
};
