<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autor_fichas', function (Blueprint $t): void {
            $t->integer('id_autor')->primary();
            $t->uuid('publico')->unique();
            $t->string('nombre_publico');
            $t->string('nacionalidad')->nullable();
            $t->date('nacimiento')->nullable();
            $t->date('fallecimiento')->nullable();
            $t->text('biografia');
            $t->string('fuente_url', 1000);
            $t->foreign('id_autor')->references('id_autor')->on('autores')->cascadeOnDelete();
        });
        foreach (['categoria' => 'categorias', 'editorial' => 'editoriales'] as $tipo => $tabla) {
            Schema::create($tipo.'_fichas', function (Blueprint $t) use ($tipo, $tabla): void {
                $t->integer('id_'.$tipo)->primary();
                $t->text('descripcion');
                $t->string('pais')->nullable();
                $t->string('fuente_url', 1000)->nullable();
                $t->foreign('id_'.$tipo)->references('id_'.$tipo)->on($tabla)->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_fichas');
        Schema::dropIfExists('categoria_fichas');
        Schema::dropIfExists('autor_fichas');
    }
};
