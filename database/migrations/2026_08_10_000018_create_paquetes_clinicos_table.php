<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paquetes_clinicos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->enum('tipo_clasificacion', [
                'prevencion_equilibrio', 'atencion_regulacion', 'optimizacion_rendimiento', 'general',
            ])->default('general');
            $table->unsignedSmallInteger('numero_sesiones')->default(1);
            $table->decimal('precio_base', 10, 2)->default(0);
            $table->json('especialidades_json')->nullable()
                ->comment('IDs de especialidades incluidas');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paquetes_clinicos');
    }
};
