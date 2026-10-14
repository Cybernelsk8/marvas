<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                ->constrained('productos_inventario')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('sucursal_id')
                ->comment('El lote (stock físico) pertenece a una sola sucursal')
                ->constrained('sucursales')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('proveedor_id')->nullable()
                ->constrained('proveedores')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('numero_lote', 60);
            $table->date('fecha_fabricacion')->nullable();
            $table->date('fecha_caducidad')->nullable();
            $table->unsignedInteger('cantidad_recibida');
            $table->unsignedInteger('cantidad_actual');
            $table->decimal('costo_unitario', 10, 2)->default(0);
            $table->enum('estado', ['disponible', 'agotado', 'vencido', 'retirado'])
                ->default('disponible');
            $table->timestamps();

            // Único por sucursal: el mismo número de lote del fabricante puede
            // repartirse en varias sedes como filas independientes.
            $table->unique(['producto_id', 'numero_lote', 'sucursal_id'], 'uq_lote_producto_numero_sucursal');
            $table->index('fecha_caducidad', 'idx_lote_caducidad');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER chk_lote_cantidad_insert
                BEFORE INSERT ON lotes_inventario
                FOR EACH ROW
                WHEN NEW.cantidad_actual > NEW.cantidad_recibida
                BEGIN
                    SELECT RAISE(ABORT, 'cantidad_actual cannot exceed cantidad_recibida');
                END
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER chk_lote_cantidad_update
                BEFORE UPDATE OF cantidad_actual, cantidad_recibida ON lotes_inventario
                FOR EACH ROW
                WHEN NEW.cantidad_actual > NEW.cantidad_recibida
                BEGIN
                    SELECT RAISE(ABORT, 'cantidad_actual cannot exceed cantidad_recibida');
                END
            SQL);

            return;
        }

        DB::statement('ALTER TABLE lotes_inventario ADD CONSTRAINT chk_lote_cantidad CHECK (cantidad_actual <= cantidad_recibida)');
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes_inventario');
    }
};
