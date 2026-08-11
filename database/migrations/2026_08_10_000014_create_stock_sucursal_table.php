<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_sucursal', function (Blueprint $table) {
            $table->foreignId('producto_id')
                ->constrained('productos_inventario')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->unsignedInteger('cantidad_actual')->default(0)
                ->comment('Mantenido por la app al registrar movimientos_inventario, dentro de una transacción con lockForUpdate()');
            $table->unsignedInteger('stock_minimo')->default(0)
                ->comment('Umbral de alerta de reabastecimiento, propio de cada sucursal');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['producto_id', 'sucursal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sucursal');
    }
};
