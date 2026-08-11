<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluacion_integral_mbi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')
                ->constrained('consultas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->text('integracion_clinica')->nullable();
            $table->json('sistemas_comprometidos')->nullable();
            $table->text('hallazgos_principales')->nullable();
            $table->text('objetivo_terapeutico')->nullable();
            $table->text('observaciones_generales')->nullable();
            $table->string('especialidad_principal', 100)->nullable();
            $table->json('especialidades_complementarias')->nullable();
            $table->json('modelo_6plus_seleccion')->nullable();
            $table->timestamps();

            $table->unique('consulta_id', 'uq_eval_consulta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_integral_mbi');
    }
};
