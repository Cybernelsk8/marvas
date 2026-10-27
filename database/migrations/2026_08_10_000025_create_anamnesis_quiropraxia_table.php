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

            // Incisos 06, 06A, 06B, 06C y 06D de la especificación. Cada ítem se guarda como
            // {estado, detalle}; 06B añade lado y fuerza. peso/talla/IMC viven en anamnesis_generales.
            $table->json('sintomas')->nullable()->comment('06: 18 síntomas, si|no|no_evaluado');
            $table->json('postura_movilidad')->nullable()->comment('06A: 12 ítems, normal|alterado|no_evaluado');
            $table->json('pruebas_neuro_ortopedicas')->nullable()
                ->comment('06B: 8 pruebas, normal|alterado|no_realizado, lado y fuerza 0-5');
            $table->text('otras_pruebas')->nullable()
                ->comment('06B: otras pruebas, interpretación y limitaciones de exploración');
            $table->json('indice_funcional')->nullable()
                ->comment('06C: sueño, desplazamiento, trabajo y recreación, bueno|ligero|moderado|malo');
            $table->json('seguridad')->nullable()->comment('06D: 9 criterios de alarma, si|no|no_evaluado');
            $table->enum('decision_manipulacion', ['indicada', 'diferida', 'contraindicada'])->nullable();
            $table->text('motivo_decision')->nullable();

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
