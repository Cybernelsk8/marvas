<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anamnesis_generales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')
                ->constrained('consultas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('especialidad_id')
                ->constrained('especialidades')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->text('motivo_consulta');
            $table->text('historia_padecimiento')->nullable();
            $table->json('antecedentes_patologicos')->nullable();
            $table->text('antecedentes_quirurgicos')->nullable();
            $table->text('antecedentes_traumaticos')->nullable();
            // antecedentes_familiares (JSON genérico) se elimina: reemplazado
            // por la tabla antecedentes_familiares_detalle (parentesco + enfermedad).
            $table->text('alergias')->nullable();
            $table->text('medicamentos_actuales')->nullable();
            $table->json('habitos')->nullable();
            $table->text('sueno')->nullable();
            $table->text('alimentacion')->nullable();
            $table->text('estado_emocional')->nullable();

            // Signos vitales: comunes a varias especialidades (ver README de datos)
            $table->string('presion_arterial', 20)->nullable()->comment('Formato libre "120/80"');
            $table->unsignedSmallInteger('frecuencia_cardiaca')->nullable()->comment('Latidos por minuto');
            $table->unsignedSmallInteger('frecuencia_respiratoria')->nullable()->comment('Respiraciones por minuto');
            $table->decimal('temperatura', 4, 1)->nullable()->comment('Grados Celsius');
            $table->unsignedSmallInteger('glicemia')->nullable()->comment('mg/dL');
            $table->decimal('peso_kg', 5, 2)->nullable();
            $table->decimal('talla_cm', 5, 2)->nullable();
            $table->decimal('imc', 4, 2)
                ->storedAs('peso_kg / (talla_cm / 100 * (talla_cm / 100))')
                ->nullable()
                ->comment('Calculado automáticamente. Único IMC del sistema.');

            $table->json('datos_formulario')->nullable()
                ->comment('Campos dinámicos adicionales por especialidad');
            $table->text('observaciones_profesional')->nullable();
            $table->timestamps();

            $table->unique('consulta_id', 'uq_anamnesis_consulta');
            $table->index('especialidad_id', 'fk_anamnesis_especialidad');
        });

        DB::statement('ALTER TABLE anamnesis_generales ADD CONSTRAINT chk_anamgen_talla CHECK (talla_cm IS NULL OR talla_cm > 0)');
        DB::statement('ALTER TABLE anamnesis_generales ADD CONSTRAINT chk_anamgen_peso CHECK (peso_kg IS NULL OR peso_kg > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('anamnesis_generales');
    }
};
