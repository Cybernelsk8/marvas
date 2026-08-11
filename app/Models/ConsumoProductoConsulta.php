<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumoProductoConsulta extends Model
{
    use HasFactory;

    protected $table = 'consumo_productos_consulta';

    const UPDATED_AT = null;

    protected $fillable = [
        'consulta_id',
        'producto_id',
        'lote_id',
        'movimiento_id',
        'cantidad',
        'cobrado_a_paciente',
        'pago_id',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'cobrado_a_paciente' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoInventario::class, 'producto_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(LoteInventario::class, 'lote_id');
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(MovimientoInventario::class, 'movimiento_id');
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }
}
