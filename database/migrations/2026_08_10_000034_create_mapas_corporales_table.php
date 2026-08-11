<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapas_corporales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anamnesis_id')
                ->constrained('anamnesis_generales')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('consulta_id')
                ->constrained('consultas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('imagen_path', 255)->nullable()
                ->comment('Ruta de la imagen SVG/PNG guardada');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('consulta_id', 'idx_mapa_consulta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapas_corporales');
    }
};
