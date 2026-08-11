<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expediente_maestro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->enum('clasificacion', [
                'prevencion_equilibrio',
                'atencion_regulacion',
                'optimizacion_rendimiento',
                'sin_clasificar',
            ])->default('sin_clasificar');
            $table->text('objetivo_principal')->nullable();
            $table->enum('fase_terapeutica', [
                'desinflamacion', 'regulacion', 'recuperacion', 'mantenimiento', 'sin_definir',
            ])->default('sin_definir');
            $table->enum('estado', ['activo', 'inactivo', 'alta', 'baja'])->default('activo');
            $table->text('notas_generales')->nullable();
            $table->timestamps();

            $table->unique('paciente_id', 'uq_expediente_paciente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expediente_maestro');
    }
};
