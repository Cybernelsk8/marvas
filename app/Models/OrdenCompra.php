<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenCompra extends Model
{
    use HasFactory;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'proveedor_id',
        'solicitado_por',
        'folio',
        'estado',
        'subtotal',
        'impuestos',
        // 'total' es columna generada (STORED), no se asigna manualmente.
        'fecha_orden',
        'fecha_recepcion_estimada',
        'fecha_recepcion_real',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'impuestos' => 'decimal:2',
            'total' => 'decimal:2',
            'fecha_orden' => 'date',
            'fecha_recepcion_estimada' => 'date',
            'fecha_recepcion_real' => 'date',
        ];
    }

    // --- Relaciones ---

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function detalle(): HasMany
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'orden_id');
    }
}
