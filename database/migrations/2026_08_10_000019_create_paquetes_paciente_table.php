<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Nota: plan_id referencia planes_tratamiento, tabla que se crea más adelante
// (depende de clasificacion_paciente -> evaluacion_integral_mbi -> consultas ->
// citas -> paquetes_paciente). Para evitar la referencia circular, la columna
// se crea aquí sin FK; la constraint se agrega cuando se crea
// planes_tratamiento, después de que ambas tablas existen.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paquetes_paciente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')
                ->constrained('expediente_maestro')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('paquete_id')
                ->constrained('paquetes_clinicos')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedSmallInteger('sesiones_totales')->default(1);
            $table->unsignedSmallInteger('sesiones_usadas')->default(0);
            $table->decimal('precio_acordado', 10, 2);
            $table->decimal('saldo_pendiente', 10, 2)->default(0);
            $table->enum('estado', ['activo', 'completado', 'cancelado', 'vencido'])->default('activo');
            $table->text('notas')->nullable();
            $table->date('inicio_at')->nullable();
            $table->date('vencimiento_at')->nullable();
            $table->timestamps();

            $table->index('expediente_id', 'idx_paquete_expediente');
            $table->index('plan_id', 'fk_paqpac_plan');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE paquetes_paciente ADD CONSTRAINT chk_sesiones CHECK (sesiones_usadas <= sesiones_totales)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('paquetes_paciente');
    }
};
