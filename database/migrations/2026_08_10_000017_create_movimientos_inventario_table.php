<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                ->constrained('productos_inventario')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('lote_id')->nullable()
                ->comment('NULL si el producto no requiere lote')
                ->constrained('lotes_inventario')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->enum('tipo_movimiento', [
                'entrada_compra',
                'entrada_ajuste',
                'salida_consulta',
                'salida_venta',
                'salida_merma',
                'salida_ajuste',
                'devolucion_proveedor',
                'devolucion_paciente',
                'transferencia',
            ]);
            $table->integer('cantidad')
                ->comment('Positivo = entrada, negativo = salida (facilita sumas para el stock)');
            $table->decimal('costo_unitario', 10, 2)->nullable();
            $table->unsignedInteger('stock_resultante')
                ->comment('Snapshot de stock_sucursal.cantidad_actual tras el movimiento, para auditoría');
            $table->enum('referencia_tipo', ['orden_compra', 'consulta', 'pago', 'ajuste_manual', 'otro'])
                ->default('ajuste_manual')
                ->comment('Origen del movimiento (polimórfico simple, igual patrón que auditoria.tabla_afectada)');
            $table->unsignedBigInteger('referencia_id')->nullable()
                ->comment('ID en la tabla indicada por referencia_tipo');
            $table->text('motivo')->nullable();
            $table->foreignId('registrado_por')
                ->comment('FK a usuarios')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['producto_id', 'created_at'], 'idx_movimiento_producto');
            $table->index(['referencia_tipo', 'referencia_id'], 'idx_movimiento_referencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
