<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas_dolor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mapa_id')
                ->constrained('mapas_corporales')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('region_id')
                ->constrained('regiones_corporales')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->enum('lateralidad', ['derecha', 'izquierda', 'central'])->default('central');
            $table->unsignedTinyInteger('eva')->comment('Escala visual analógica 0-10');
            $table->enum('nivel', ['sin_dolor', 'leve', 'moderado', 'intenso', 'muy_intenso'])
                ->comment('Derivado del EVA: 0, 1-3, 4-6, 7-8, 9-10');
            $table->json('tipos')->nullable()->comment('Lista: punzante, quemante, opresivo...');
            $table->json('irradiacion')->nullable();
            $table->string('inicio', 120)->nullable();
            $table->json('patron')->nullable()->comment('Lista: constante, nocturno, en reposo...');
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->unique(['mapa_id', 'region_id', 'lateralidad'], 'uq_zona_mapa_region_lado');
            $table->index('region_id', 'idx_zona_region');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE zonas_dolor ADD CONSTRAINT chk_eva CHECK (eva BETWEEN 0 AND 10)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas_dolor');
    }
};
