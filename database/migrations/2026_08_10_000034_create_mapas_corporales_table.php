<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapas_corporales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')
                ->unique()
                ->constrained('consultas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->enum('estado', ['no_evaluado', 'sin_dolor', 'con_dolor'])->default('no_evaluado');
            $table->unsignedTinyInteger('eva_maximo')->nullable()
                ->comment('EVA máximo 0-10 entre las zonas; alimenta la intensidad del inciso 02');
            $table->string('zona_mayor_dolor', 120)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapas_corporales');
    }
};
