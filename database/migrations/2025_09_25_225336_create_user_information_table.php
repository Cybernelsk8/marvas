<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_information', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 60);
            $table->string('apellidos', 60);
            $table->string('cui', 13)->unique();
            $table->string('telefono',10);
            $table->date('fecha_nacimiento');
            $table->string('correo')->unique();
            $table->enum('sexo',['F','M'])->default('M');
            $table->string('foto')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('desarrollo-social')->dropIfExists('user_information');
    }
};
