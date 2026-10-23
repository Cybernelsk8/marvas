<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursal_evaluadores_mbi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')
                ->unique()
                ->constrained('sucursales')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('profesional_id')
                ->constrained('profesionales')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursal_evaluadores_mbi');
    }
};
