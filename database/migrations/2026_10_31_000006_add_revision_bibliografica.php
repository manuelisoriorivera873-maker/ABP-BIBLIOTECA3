<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('libro_fichas', function (Blueprint $t): void {
            $t->string('idioma', 10)->nullable();
            $t->text('revision_bibliografica')->nullable();
            $t->timestamp('portada_verificada_en')->nullable();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('La evidencia bibliográfica se conserva; requiere revisión y respaldo.');
    }
};
