<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimientos_registro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')
                ->constrained('expediente_maestro')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('consulta_id')
                ->constrained('consultas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('plantilla_id')
                ->constrained('consentimientos_plantilla')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('profesional_id')
                ->constrained('profesionales')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->boolean('confirma_lectura_fisica')->default(false);
            $table->boolean('confirma_resolucion_dudas')->default(false);
            $table->boolean('acepta_voluntariamente')->default(false);
            $table->string('firma_digital_path', 255)->nullable()->comment('Ruta a imagen de firma');
            $table->string('huella_dactilar_path', 255)->nullable()->comment('Ruta a imagen de huella');
            $table->string('ip_registro', 45)->nullable();
            $table->string('dispositivo_info', 255)->nullable();
            $table->string('pdf_comprobante_path', 255)->nullable();
            $table->timestamp('aceptado_at')->useCurrent();

            $table->unique('consulta_id', 'uq_consentimiento_consulta');
            $table->index('expediente_id', 'idx_consent_expediente');
        });

        DB::statement('ALTER TABLE consentimientos_registro ADD CONSTRAINT chk_consent_completo CHECK (confirma_lectura_fisica = 1 AND confirma_resolucion_dudas = 1 AND acepta_voluntariamente = 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimientos_registro');
    }
};
