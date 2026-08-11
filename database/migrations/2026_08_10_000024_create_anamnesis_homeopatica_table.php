<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anamnesis_homeopatica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anamnesis_id')
                ->constrained('anamnesis_generales')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->text('aspecto_paciente')->nullable()
                ->comment('Cómo va vestido, forma de dirigirse al homeópata, discurso, aspecto físico');
            $table->text('sintomas_locales_generales')->nullable();
            $table->text('sintomas_mentales')->nullable()
                ->comment('Patrones de sueño, nerviosismo, tensión, ánimo, memoria');
            $table->text('sintomas_biopatograficos')->nullable()
                ->comment('Enfermedades de infancia, inmunizaciones, hospitalizaciones');
            $table->text('diagnostico_posible')->nullable();
            $table->text('tratamiento_indicado')->nullable()
                ->comment('Remedios homeopáticos, sueroterapia asociada, etc.');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique('anamnesis_id', 'uq_anamnesis_homeo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anamnesis_homeopatica');
    }
};
