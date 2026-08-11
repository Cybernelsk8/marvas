<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')
                ->constrained('expediente_maestro')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('sucursal_id')
                ->comment('Independiente de citas.sucursal_id por si la consulta es walk-in')
                ->constrained('sucursales')
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
            $table->foreignId('cita_id')->nullable()
                ->comment('NULL si la consulta se abrió sin cita previa (walk-in)')
                ->constrained('citas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('fecha_hora');
            $table->enum('estado', ['abierta', 'en_proceso', 'cerrada', 'cancelada'])
                ->default('abierta');
            $table->text('observaciones')->nullable();
            $table->unsignedSmallInteger('numero_consulta')->default(1)
                ->comment('Número de consulta del paciente en esta especialidad. Calculado por la app dentro de una transacción con lock.');
            $table->timestamps();

            $table->index('expediente_id', 'idx_consultas_expediente');
            $table->index('profesional_id', 'idx_consultas_profesional');
            $table->index('fecha_hora', 'idx_consultas_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};
