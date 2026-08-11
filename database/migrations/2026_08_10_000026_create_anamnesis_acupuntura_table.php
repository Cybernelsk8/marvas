<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anamnesis_acupuntura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anamnesis_id')
                ->constrained('anamnesis_generales')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->text('diagnostico_energetico')->nullable();

            // Pulso: 12 lecturas (cun/guan/chi x superficial/profundo x mano der/izq)
            $table->string('pulso_cun_der_superficial', 60)->nullable();
            $table->string('pulso_cun_der_profundo', 60)->nullable();
            $table->string('pulso_guan_der_superficial', 60)->nullable();
            $table->string('pulso_guan_der_profundo', 60)->nullable();
            $table->string('pulso_chi_der_superficial', 60)->nullable();
            $table->string('pulso_chi_der_profundo', 60)->nullable();
            $table->string('pulso_cun_izq_superficial', 60)->nullable();
            $table->string('pulso_cun_izq_profundo', 60)->nullable();
            $table->string('pulso_guan_izq_superficial', 60)->nullable();
            $table->string('pulso_guan_izq_profundo', 60)->nullable();
            $table->string('pulso_chi_izq_superficial', 60)->nullable();
            $table->string('pulso_chi_izq_profundo', 60)->nullable();

            // Lengua: JSON array por campo, permite multi-selección (ver README)
            $table->json('lengua_forma')->nullable();
            $table->json('lengua_color')->nullable();
            $table->json('lengua_otros_rasgos')->nullable();
            $table->json('saburra_color')->nullable();
            $table->json('saburra_espesor')->nullable();
            $table->json('saburra_distribucion')->nullable();
            $table->enum('humedad', ['seca', 'humeda', 'pegajosa'])->nullable();

            // Marcos diagnósticos de Medicina Tradicional China
            $table->json('diagnostico_8_reglas')->nullable();
            $table->json('diagnostico_6_niveles')->nullable();
            $table->json('diagnostico_4_capas')->nullable();
            $table->json('organos_zang_fu')->nullable();

            $table->text('principio_terapeutico')->nullable();
            $table->json('puntos_sugeridos')->nullable()
                ->comment('categoria: distal|local|maestro dentro de cada elemento del array');
            $table->text('observaciones')->nullable();

            $table->unique('anamnesis_id', 'uq_anamnesis_acupuntura');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anamnesis_acupuntura');
    }
};
