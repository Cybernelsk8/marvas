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
            $table->string('zona', 60)->comment('cervical, lumbar, hombro_der, etc.');
            $table->enum('lateralidad', ['derecha', 'izquierda', 'bilateral', 'central'])
                ->default('central');
            $table->unsignedTinyInteger('intensidad')->default(1)->comment('Escala 1-10');
            $table->set('tipo_dolor', [
                'punzante', 'quemante', 'opresivo', 'irradiado', 'constante', 'intermitente',
            ]);
            $table->text('descripcion')->nullable();
            $table->decimal('coord_x', 5, 2)->nullable()
                ->comment('Coordenada X en el SVG del mapa (0-100%)');
            $table->decimal('coord_y', 5, 2)->nullable()
                ->comment('Coordenada Y en el SVG del mapa (0-100%)');

            $table->index('mapa_id', 'idx_zona_mapa');
        });

        DB::statement('ALTER TABLE zonas_dolor ADD CONSTRAINT chk_intensidad CHECK (intensidad BETWEEN 1 AND 10)');
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas_dolor');
    }
};
