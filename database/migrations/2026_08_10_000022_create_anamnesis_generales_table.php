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
            $table->text('motivo_consulta')->nullable();
            $table->unsignedTinyInteger('intensidad_dolor')->nullable()
                ->comment('EVA 0-10 del inciso 02; se alimenta del EVA máximo del mapa de dolor');
            $table->string('zona_dolor_principal', 120)->nullable();
            $table->json('antecedentes_estados')->nullable()
                ->comment('Inciso 03: {bloque: {estado: si|no|no_evaluado, detalle}}');
            $table->json('revision_sistemas')->nullable()
                ->comment('Inciso 04: 15 sistemas {estado: sin_sintomas|con_sintomas|no_evaluado, hallazgos}');
            $table->text('historia_padecimiento')->nullable();
            // Los antecedentes (03) y hábitos viven en antecedentes_estados y datos_formulario;
            // antecedentes_familiares en antecedentes_familiares_detalle (parentesco + enfermedad).

            // Signos vitales: comunes a varias especialidades (ver README de datos)
            $table->unsignedSmallInteger('presion_sistolica')->nullable()->comment('mmHg');
            $table->unsignedSmallInteger('presion_diastolica')->nullable()->comment('mmHg');
            $table->unsignedSmallInteger('frecuencia_cardiaca')->nullable()->comment('Latidos por minuto');
            $table->unsignedSmallInteger('frecuencia_respiratoria')->nullable()->comment('Respiraciones por minuto');
            $table->decimal('temperatura', 4, 1)->nullable()->comment('Grados Celsius');
            $table->unsignedSmallInteger('glicemia')->nullable()->comment('mg/dL');
            $table->decimal('peso_kg', 5, 2)->nullable();
            $table->decimal('talla_cm', 5, 2)->nullable();
            $table->decimal('imc', 4, 2)
                ->storedAs('peso_kg / (talla_cm / 100.0 * (talla_cm / 100.0))')
                ->nullable()
                ->comment('Calculado automáticamente. Único IMC del sistema.');

            $table->json('datos_formulario')->nullable()
                ->comment('Campos dinámicos adicionales por especialidad');
            $table->text('observaciones_profesional')->nullable();
            $table->timestamps();

            $table->unique('consulta_id', 'uq_anamnesis_consulta');
            $table->index('especialidad_id', 'fk_anamnesis_especialidad');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE anamnesis_generales ADD CONSTRAINT chk_anamgen_talla CHECK (talla_cm IS NULL OR talla_cm > 0)');
            DB::statement('ALTER TABLE anamnesis_generales ADD CONSTRAINT chk_anamgen_peso CHECK (peso_kg IS NULL OR peso_kg > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('anamnesis_generales');
    }
};
