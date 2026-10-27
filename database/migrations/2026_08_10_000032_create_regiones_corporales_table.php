<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de regiones del mapa corporal (mapa-corporal.png); se amplía agregando filas.
        Schema::create('regiones_corporales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique()->comment('Clave estable usada por el SVG, ej. pectorales');
            $table->string('nombre', 80);
            $table->enum('vista', ['anterior', 'posterior']);
            $table->boolean('tiene_lateralidad')->default(true)
                ->comment('false si la región es central (abdominales, lumbar...)');
            $table->json('trazos')->nullable()
                ->comment('Contornos SVG por lateralidad en coordenadas de mapa-corporal.png: {"derecha":"M..Z","izquierda":"M..Z","central":"M..Z"}');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['vista', 'orden'], 'idx_region_vista_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regiones_corporales');
    }
};
