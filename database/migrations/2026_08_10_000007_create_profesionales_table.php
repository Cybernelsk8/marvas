<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesionales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('nombre_completo', 200);
            $table->string('cedula_profesional', 60)->nullable();
            // especialidad_principal (varchar libre) se elimina: se deriva de
            // profesional_especialidades.es_principal para evitar duplicidad.
            $table->string('color_agenda_hex', 7)->nullable()
                ->comment('Color para calendario de citas');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique('user_id', 'uq_profesionales_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionales');
    }
};
