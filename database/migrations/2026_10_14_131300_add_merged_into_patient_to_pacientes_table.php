<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->foreignId('fusionado_en_paciente_id')
                ->nullable()
                ->after('activo')
                ->constrained('pacientes')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fusionado_en_paciente_id');
        });
    }
};
