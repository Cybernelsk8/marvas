<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';

    const UPDATED_AT = null;

    protected $fillable = [
        'producto_id',
        'lote_id',
        'tipo_movimiento',
        'cantidad',
        'costo_unitario',
        'stock_resultante',
        'referencia_tipo',
        'referencia_id',
        'motivo',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'costo_unitario' => 'decimal:2',
            'stock_resultante' => 'integer',
        ];
    }

    // --- Relaciones ---

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoInventario::class, 'producto_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(LoteInventario::class, 'lote_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    // Nota: referencia_tipo/referencia_id son una referencia polimórfica
    // "manual" (no usan morphTo de Eloquent porque referencia_tipo guarda
    // una etiqueta de negocio como 'orden_compra', no el FQCN del modelo).
    // Si se prefiere morphTo real, se puede definir un morphMap en
    // AppServiceProvider y cambiar estas dos columnas a *_type/*_id.
}
