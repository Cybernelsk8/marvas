<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('slug', 100);
            $table->text('descripcion')->nullable();
            $table->string('color_hex', 7)->nullable()
                ->comment('Color para UI, ej: #1D9E75');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique('slug', 'uq_especialidades_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('especialidades');
    }
};
