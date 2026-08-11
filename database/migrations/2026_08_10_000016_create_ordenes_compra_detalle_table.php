<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_id')
                ->constrained('ordenes_compra')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('producto_id')
                ->constrained('productos_inventario')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->unsignedInteger('cantidad_solicitada');
            $table->unsignedInteger('cantidad_recibida')->default(0);
            $table->decimal('costo_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2)->storedAs('cantidad_solicitada * costo_unitario');
        });

        DB::statement('ALTER TABLE ordenes_compra_detalle ADD CONSTRAINT chk_ordendet_recibida CHECK (cantidad_recibida <= cantidad_solicitada)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra_detalle');
    }
};
