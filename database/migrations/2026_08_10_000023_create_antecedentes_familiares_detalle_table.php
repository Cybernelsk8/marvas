<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antecedentes_familiares_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anamnesis_id')
                ->constrained('anamnesis_generales')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->enum('parentesco', [
                'padre', 'madre', 'hermano', 'hermana',
                'abuelo_paterno', 'abuela_paterna', 'abuelo_materno', 'abuela_materna', 'otro',
            ]);
            $table->string('enfermedad', 100)
                ->comment('Ej: diabetes, hipertension, cancer, artritis, artrosis, hernias, alzheimer, osteoporosis');
            $table->boolean('presente')->default(false);
            $table->text('observaciones')->nullable();

            $table->index('anamnesis_id', 'idx_antfamdet_anamnesis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antecedentes_familiares_detalle');
    }
};
