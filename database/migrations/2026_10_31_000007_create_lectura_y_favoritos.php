<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('libro_fichas', function (Blueprint $t) {
            $t->string('obra_fuente', 100)->nullable()->index();
        });
        Schema::create('usuario_favoritos', function (Blueprint $t) {
            $t->integer('id_usuario');
            $t->integer('id_libro');
            $t->timestamp('creado_en')->useCurrent();
            $t->primary(['id_usuario', 'id_libro']);
            $t->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            $t->foreign('id_libro')->references('id_libro')->on('libros')->cascadeOnDelete();
        });
        Schema::create('recursos_digitales', function (Blueprint $t) {
            $t->id();
            $t->uuid('publico')->unique();
            $t->integer('id_libro');
            $t->foreign('id_libro')->references('id_libro')->on('libros')->cascadeOnDelete();
            $t->string('tipo', 20); // integrado, externo, previa
            $t->string('idioma', 10);
            $t->string('formato', 10);
            $t->string('edicion', 250);
            $t->string('url', 1000)->nullable();
            $t->string('ruta_privada', 500)->nullable();
            $t->string('sha256', 64)->nullable();
            $t->string('fuente', 1000);
            $t->text('licencia');
            $t->string('licencia_url', 1000);
            $t->boolean('disponible')->default(false);
            $t->timestamp('verificado_en')->nullable();
        });
        Schema::create('lectura_progresos', function (Blueprint $t) {
            $t->integer('id_usuario');
            $t->foreignId('id_recurso')->constrained('recursos_digitales')->cascadeOnDelete();
            $t->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            $t->string('posicion', 500);
            $t->unsignedSmallInteger('tamano')->default(100);
            $t->timestamp('actualizado_en');
            $t->primary(['id_usuario', 'id_recurso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lectura_progresos');
        Schema::dropIfExists('recursos_digitales');
        Schema::dropIfExists('usuario_favoritos');
        Schema::table('libro_fichas', fn (Blueprint $t) => $t->dropColumn('obra_fuente'));
    }
};
