<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraDetalle extends Model
{
    use HasFactory;

    protected $table = 'ordenes_compra_detalle';

    public $timestamps = false;

    protected $fillable = [
        'orden_id',
        'producto_id',
        'cantidad_solicitada',
        'cantidad_recibida',
        'costo_unitario',
        // 'subtotal' es columna generada (STORED), no se asigna manualmente.
    ];

    protected function casts(): array
    {
        return [
            'cantidad_solicitada' => 'integer',
            'cantidad_recibida' => 'integer',
            'costo_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    // --- Relaciones ---

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoInventario::class, 'producto_id');
    }
}
