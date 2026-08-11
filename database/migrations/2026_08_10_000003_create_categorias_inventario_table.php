<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_inventario', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('slug', 100);
            $table->enum('tipo', [
                'medicamento',
                'remedio_homeopatico',
                'insumo_clinico',
                'equipo',
                'suplemento',
                'otro',
            ])->default('otro');
            $table->text('descripcion')->nullable();
            $table->boolean('activa')->default(true);

            $table->unique('slug', 'uq_categoria_inventario_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_inventario');
    }
};
