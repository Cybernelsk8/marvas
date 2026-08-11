<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()
                ->comment('NULL si es acción del sistema')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('tabla_afectada', 100);
            $table->unsignedBigInteger('registro_id');
            $table->enum('accion', ['crear', 'editar', 'eliminar', 'ver', 'exportar', 'login', 'logout']);
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tabla_afectada', 'registro_id'], 'idx_auditoria_tabla');
            $table->index('created_at', 'idx_auditoria_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
