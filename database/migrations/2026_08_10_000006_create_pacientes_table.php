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
            $table->string('codigo_expediente', 20)
                ->comment('Código único legible: MBI-00001');
            $table->string('nombre', 100);
            $table->string('apellidos', 150);
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['masculino', 'femenino', 'otro', 'no_especificado'])
                ->default('no_especificado');
            $table->enum('estado_civil', [
                'soltero', 'casado', 'divorciado', 'viudo', 'union_libre', 'otro',
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

            $table->unique('codigo_expediente', 'uq_pacientes_codigo');
            $table->unique('numero_dpi', 'uq_pacientes_dpi');
            $table->index(['nombre', 'apellidos'], 'idx_pacientes_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
