<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimientos_plantilla', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20);
            $table->string('titulo', 200);
            $table->text('descripcion_terapias')->nullable();
            $table->json('terapias_cubiertas')->nullable();
            $table->date('vigente_desde');
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->unique('version', 'uq_plantilla_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimientos_plantilla');
    }
};
