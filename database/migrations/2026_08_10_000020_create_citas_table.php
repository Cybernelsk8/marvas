<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nota de negocio: MariaDB no soporta exclusion constraints. La app debe
// validar en una transacción (con lockForUpdate) que no existan citas
// traslapadas para el mismo profesional antes de insertar/actualizar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')
                ->constrained('expediente_maestro')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('profesional_id')
                ->constrained('profesionales')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('especialidad_id')
                ->constrained('especialidades')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('paquete_paciente_id')->nullable()
                ->comment('NULL si es sesión suelta')
                ->constrained('paquetes_paciente')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('agendada_por')
                ->comment('FK a usuarios')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('fecha_hora_inicio');
            $table->dateTime('fecha_hora_fin');
            $table->unsignedSmallInteger('duracion_minutos')->default(60);
            $table->enum('tipo_cita', [
                'primera_vez',
                'seguimiento',
                'control',
                'evaluacion_integral',
                'procedimiento',
            ])->default('seguimiento');
            $table->enum('estado', [
                'agendada',
                'confirmada',
                'en_atencion',
                'finalizada',
                'cancelada',
                'reprogramada',
                'no_asistio',
            ])->default('agendada');
            $table->text('motivo_cancelacion')->nullable();
            $table->text('notas_recepcion')->nullable();
            $table->boolean('recordatorio_enviado')->default(false);
            $table->timestamps();

            $table->index(['profesional_id', 'fecha_hora_inicio'], 'idx_citas_profesional');
            $table->index('expediente_id', 'idx_citas_expediente');
            $table->index('fecha_hora_inicio', 'idx_citas_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
