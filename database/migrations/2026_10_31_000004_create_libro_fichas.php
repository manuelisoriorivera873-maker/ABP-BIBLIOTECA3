<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libro_fichas', function (Blueprint $table): void {
            $table->integer('id_libro')->primary();
            $table->uuid('publico')->unique();
            $table->string('titulo_verificado', 200)->nullable();
            $table->string('isbn_verificado', 20)->nullable();
            $table->string('editorial_verificada')->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->text('sinopsis')->nullable();
            $table->string('portada_url', 1000)->nullable();
            $table->string('fuente_url', 1000)->nullable();
            $table->string('portada_fuente', 1000)->nullable();
            $table->timestamp('verificado_en')->nullable();
            $table->foreign('id_libro')->references('id_libro')->on('libros');
        });
        DB::table('libros')->orderBy('id_libro')->chunk(500, function ($libros): void {
            foreach ($libros as $libro) {
                DB::table('libro_fichas')->insert(['id_libro' => $libro->id_libro, 'publico' => (string) Str::uuid()]);
            }
        });
        Schema::create('solicitud_envios', function (Blueprint $table): void {
            $table->uuid('token')->primary();
            $table->unsignedBigInteger('id_solicitud')->unique();
            $table->foreign('id_solicitud')->references('id_solicitud')->on('solicitudes_prestamo');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Los identificadores públicos y las fichas se conservan. Requiere revisión y respaldo.');
    }
};
