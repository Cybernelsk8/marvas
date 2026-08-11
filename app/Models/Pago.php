<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    const UPDATED_AT = null;

    protected $fillable = [
        'expediente_id',
        'cita_id',
        'paquete_paciente_id',
        'cobrado_por',
        'tipo_cobro',
        'concepto',
        'monto_total',
        'monto_pagado',
        // 'saldo' es columna generada (STORED), no se asigna manualmente.
        'metodo_pago',
        'numero_comprobante',
        'notas',
        'pagado_at',
    ];

    protected function casts(): array
    {
        return [
            'monto_total' => 'decimal:2',
            'monto_pagado' => 'decimal:2',
            'saldo' => 'decimal:2',
            'pagado_at' => 'datetime',
        ];
    }

    // --- Relaciones ---

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(ExpedienteMaestro::class, 'expediente_id');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function paquetePaciente(): BelongsTo
    {
        return $this->belongsTo(PaquetePaciente::class, 'paquete_paciente_id');
    }

    public function cobradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cobrado_por');
    }

    public function consumosProductos(): HasMany
    {
        return $this->hasMany(ConsumoProductoConsulta::class, 'pago_id');
    }
}
