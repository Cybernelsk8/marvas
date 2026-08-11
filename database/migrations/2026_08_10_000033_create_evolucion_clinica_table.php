<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolucion_clinica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')
                ->constrained('consultas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('especialidad_id')
                ->constrained('especialidades')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('profesional_id')
                ->constrained('profesionales')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->json('hallazgos')->nullable()
                ->comment('Diferente estructura según especialidad');
            $table->text('tratamiento_aplicado')->nullable();
            $table->text('respuesta_paciente')->nullable();
            $table->text('proxima_conducta')->nullable();
            $table->boolean('necesita_cambio_plan')->default(false);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('consulta_id', 'idx_evolucion_consulta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolucion_clinica');
    }
};
