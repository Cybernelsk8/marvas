<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->nullable()
                ->constrained('expediente_maestro')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('cita_id')->nullable()
                ->constrained('citas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('usuario_id')->nullable()
                ->comment('Usuario destino (profesional, recepción)')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->enum('tipo', ['recordatorio_cita', 'pago_pendiente', 'alerta_clinica', 'aviso_sistema']);
            $table->enum('canal', ['whatsapp', 'email', 'sms', 'sistema'])->default('sistema');
            $table->enum('estado', ['pendiente', 'enviado', 'fallido', 'cancelado'])->default('pendiente');
            $table->text('contenido');
            $table->dateTime('programado_at');
            $table->dateTime('enviado_at')->nullable();
            $table->unsignedTinyInteger('intento')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['programado_at', 'estado'], 'idx_notif_programado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
