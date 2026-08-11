<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')
                ->constrained('categorias_inventario')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('proveedor_principal_id')->nullable()
                ->constrained('proveedores')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('especialidad_id')->nullable()
                ->comment('NULL si el producto aplica a varias especialidades')
                ->constrained('especialidades')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('sku', 50);
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->enum('unidad_medida', [
                'pieza', 'caja', 'frasco', 'ampolleta', 'ml', 'mg', 'g', 'sobre', 'par', 'kit',
            ])->default('pieza');
            $table->boolean('requiere_lote')->default(false)
                ->comment('1 si debe controlarse por lote/caducidad (medicamentos, remedios)');
            // stock_minimo y stock_actual se eliminan de aquí: el stock físico
            // ahora vive por sucursal en la tabla stock_sucursal (multi-sede).
            $table->decimal('costo_promedio', 10, 2)->default(0)
                ->comment('Costo de compra promedio, para valorización de inventario');
            $table->decimal('precio_venta', 10, 2)->nullable()
                ->comment('NULL si el producto no se vende directo al paciente');
            $table->boolean('es_vendible')->default(false)
                ->comment('1 si se cobra al paciente');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique('sku', 'uq_producto_sku');
            $table->index('nombre', 'idx_producto_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos_inventario');
    }
};
