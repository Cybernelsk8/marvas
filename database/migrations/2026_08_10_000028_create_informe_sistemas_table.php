<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informe_sistemas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')
                ->constrained('consultas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->json('sistema_nervioso')->nullable();
            $table->json('sistema_digestivo')->nullable();
            $table->json('sistema_respiratorio')->nullable();
            $table->json('sistema_urinario')->nullable();
            $table->json('sistema_endocrino')->nullable();
            $table->json('sistema_musculoesqueletico')->nullable();
            $table->json('sistema_ginecologico')->nullable()->comment('Ginecológico / Urológico');
            $table->json('piel_faneras')->nullable();
            $table->text('observaciones_generales')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('consulta_id', 'uq_informe_consulta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informe_sistemas');
    }
};
