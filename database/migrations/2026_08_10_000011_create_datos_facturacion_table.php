<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datos_facturacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('nit', 20)
                ->comment('NIT o "CF" para consumidor final');
            $table->string('nombre_razon_social', 200);
            $table->text('direccion_fiscal')->nullable();
            $table->boolean('es_predeterminado')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('paciente_id', 'idx_facturacion_paciente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datos_facturacion');
    }
};
