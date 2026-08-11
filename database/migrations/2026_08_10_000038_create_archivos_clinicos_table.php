<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos_clinicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')
                ->constrained('expediente_maestro')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('consulta_id')->nullable()
                ->comment('NULL si el archivo es del expediente general')
                ->constrained('consultas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('subido_por')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->enum('tipo_archivo', [
                'laboratorio',
                'imagen',
                'radiografia',
                'resonancia',
                'documento',
                'consentimiento',
                'foto_evolutiva',
                'mapa_corporal',
                'otro',
            ]);
            $table->string('nombre_original', 255);
            $table->string('path_almacenamiento', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano_bytes')->default(0);
            $table->text('descripcion')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('expediente_id', 'idx_archivos_expediente');
            $table->index('consulta_id', 'idx_archivos_consulta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos_clinicos');
    }
};
