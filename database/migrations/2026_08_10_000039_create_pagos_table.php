<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')
                ->constrained('expediente_maestro')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('cita_id')->nullable()
                ->constrained('citas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('paquete_paciente_id')->nullable()
                ->constrained('paquetes_paciente')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('cobrado_por')
                ->comment('FK a usuarios (rol caja)')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->enum('tipo_cobro', [
                'consulta',
                'evaluacion_integral',
                'paquete',
                'abono',
                'producto',
                'medicamento',
                'otro',
            ]);
            $table->string('concepto', 255);
            $table->decimal('monto_total', 10, 2);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            // Sin CHECK monto_pagado <= monto_total: se valida en la aplicación (decisión de negocio).
            $table->decimal('saldo', 10, 2)->storedAs('monto_total - monto_pagado');
            $table->enum('metodo_pago', [
                'efectivo',
                'tarjeta_credito',
                'tarjeta_debito',
                'transferencia',
                'otro',
            ])->default('efectivo');
            $table->string('numero_comprobante', 60)->nullable();
            $table->text('notas')->nullable();
            $table->timestamp('pagado_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->index('expediente_id', 'idx_pagos_expediente');
            $table->index('pagado_at', 'idx_pagos_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
