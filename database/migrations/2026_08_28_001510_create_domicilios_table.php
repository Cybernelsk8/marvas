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
        Schema::create('domicilios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipio_id')->constrained();
            $table->integer('zona_id')->nullable();
            $table->string('colonia')->nullable();
            $table->string('direccion');
            $table->foreignId('user_information_id')->constrained('user_information');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('desarrollo-social')->dropIfExists('domicilios');
    }
};
