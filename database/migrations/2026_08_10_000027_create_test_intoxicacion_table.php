<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_intoxicacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')
                ->constrained('consultas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->json('bloques')
                ->comment('[{"bloque":"digestivo","preguntas":[{"texto":"...","frecuencia":3,"puntaje":2}]}]');
            $table->unsignedSmallInteger('puntaje_total')->default(0);
            $table->enum('interpretacion', ['bajo', 'moderado', 'alto', 'muy_alto'])->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('consulta_id', 'uq_test_consulta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_intoxicacion');
    }
};
