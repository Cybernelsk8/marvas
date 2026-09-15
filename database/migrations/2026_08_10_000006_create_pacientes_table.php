<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 150);
            $table->string('apellidos', 150);
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['M', 'F', 'otro'])
                ->default('otro');
            $table->enum('estado_civil', [
                'soltero',
                'casado',
                'divorciado',
                'viudo',
                'union_libre',
                'otro',
            ])->nullable();
            $table->string('religion', 60)->nullable();
            $table->string('numero_dpi', 20)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('telefono_emergencia', 20)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('direccion')->nullable();
            $table->string('ocupacion', 100)->nullable();
            $table->string('como_nos_conocio', 100)->nullable();
            $table->string('foto_path', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique('numero_dpi', 'uq_pacientes_dpi');
            $table->index(['nombres', 'apellidos'], 'idx_pacientes_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
