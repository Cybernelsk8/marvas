<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('solicitado_por')
                ->comment('FK a usuarios')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('folio', 30);
            $table->enum('estado', ['borrador', 'enviada', 'parcial', 'recibida', 'cancelada'])
                ->default('borrador');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('impuestos', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->storedAs('subtotal + impuestos');
            $table->date('fecha_orden');
            $table->date('fecha_recepcion_estimada')->nullable();
            $table->date('fecha_recepcion_real')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique('folio', 'uq_orden_folio');
            $table->index('estado', 'idx_orden_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
