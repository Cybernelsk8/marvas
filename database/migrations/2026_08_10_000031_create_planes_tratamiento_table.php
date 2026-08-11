<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_tratamiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')
                ->constrained('expediente_maestro')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('clasificacion_id')
                ->constrained('clasificacion_paciente')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->text('diagnostico_integrativo');
            $table->json('terapias_indicadas')
                ->comment('[{"especialidad":"quiropraxia","descripcion":"..."}]');
            $table->unsignedTinyInteger('frecuencia_semanal')->default(1);
            $table->unsignedSmallInteger('numero_sesiones')->default(1);
            $table->text('indicaciones_casa')->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('fase_actual', [
                'desinflamacion', 'regulacion', 'recuperacion', 'mantenimiento',
            ])->default('regulacion');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('expediente_id', 'idx_plan_expediente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_tratamiento');
    }
};
