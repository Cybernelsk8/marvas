<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anamnesis_quiropraxia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anamnesis_id')
                ->constrained('anamnesis_generales')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->text('cirugias')->nullable();
            $table->text('fracturas')->nullable();
            $table->text('hospitalizaciones')->nullable();
            $table->boolean('implantes_metales')->default(false);
            $table->text('detalle_implantes')->nullable();
            $table->boolean('embarazo')->default(false);
            $table->unsignedTinyInteger('semanas_gestacion')->nullable();
            $table->boolean('adormecimientos')->default(false);
            $table->text('zonas_adormecidas')->nullable();
            $table->boolean('ciatica')->default(false);
            $table->boolean('dolor_cabeza')->default(false);
            $table->boolean('vertigo')->default(false);
            $table->boolean('atm')->default(false);
            $table->text('detalle_atm')->nullable();
            $table->text('postura')->nullable();

            // peso_kg / talla_cm / imc viven en anamnesis_generales (evita duplicar el IMC).

            // Índice de Evaluación Funcional
            $table->enum('calidad_sueno', ['bueno', 'ligero', 'moderado', 'malo'])->nullable();
            $table->enum('desplazamiento', ['sin_dolor', 'ligero', 'moderado', 'severo'])->nullable()
                ->comment('Dolor en grandes recorridos / desplazamiento');
            $table->enum('tiempo_trabajo', [
                'tiempo_normal', 'se_extralimita', 'no_se_extralimita', 'no_puede_trabajar',
            ])->nullable();
            $table->enum('recreacion', [
                'todas_actividades', 'algunas_actividades', 'pocas_actividades', 'ninguna_actividad',
            ])->nullable();

            $table->text('diagnostico')->nullable();
            $table->text('plan_seguimiento_inicial')->nullable();
            $table->text('observaciones')->nullable();

            $table->unique('anamnesis_id', 'uq_anamnesis_quiro');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anamnesis_quiropraxia');
    }
};
