<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clasificacion_paciente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')
                ->constrained('expediente_maestro')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('evaluacion_id')
                ->constrained('evaluacion_integral_mbi')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->enum('categoria', [
                'prevencion_equilibrio',
                'atencion_regulacion',
                'optimizacion_rendimiento',
            ]);
            $table->text('justificacion');
            $table->text('objetivo_caso')->nullable();
            $table->enum('fase_inicial', [
                'desinflamacion',
                'regulacion',
                'recuperacion',
                'mantenimiento',
            ])->nullable();
            $table->foreignId('clasificado_por')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->timestamp('clasificado_at')->useCurrent();

            $table->index('expediente_id', 'idx_clasif_expediente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clasificacion_paciente');
    }
};
