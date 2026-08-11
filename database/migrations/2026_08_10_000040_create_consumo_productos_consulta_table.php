<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumo_productos_consulta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')
                ->constrained('consultas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('producto_id')
                ->constrained('productos_inventario')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('lote_id')->nullable()
                ->constrained('lotes_inventario')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('movimiento_id')->nullable()
                ->comment('Movimiento de salida generado por este consumo')
                ->constrained('movimientos_inventario')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->unsignedInteger('cantidad')->default(1);
            $table->boolean('cobrado_a_paciente')->default(false)
                ->comment('Si generó línea en pagos (insumo interno vs. venta al paciente)');
            $table->foreignId('pago_id')->nullable()
                ->comment('FK a pagos cuando cobrado_a_paciente=1')
                ->constrained('pagos')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->text('notas')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('consulta_id', 'idx_consumo_consulta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumo_productos_consulta');
    }
};
