<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesional_sucursales', function (Blueprint $table) {
            $table->foreignId('profesional_id')
                ->constrained('profesionales')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->boolean('es_principal')->default(false);

            $table->primary(['profesional_id', 'sucursal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesional_sucursales');
    }
};
