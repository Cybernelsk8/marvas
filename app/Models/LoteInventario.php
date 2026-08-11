<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoteInventario extends Model
{
    use HasFactory;

    protected $table = 'lotes_inventario';

    protected $fillable = [
        'producto_id',
        'sucursal_id',
        'proveedor_id',
        'numero_lote',
        'fecha_fabricacion',
        'fecha_caducidad',
        'cantidad_recibida',
        'cantidad_actual',
        'costo_unitario',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_fabricacion' => 'date',
            'fecha_caducidad' => 'date',
            'cantidad_recibida' => 'integer',
            'cantidad_actual' => 'integer',
            'costo_unitario' => 'decimal:2',
        ];
    }

    // --- Relaciones ---

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoInventario::class, 'producto_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'lote_id');
    }

    public function consumosEnConsultas(): HasMany
    {
        return $this->hasMany(ConsumoProductoConsulta::class, 'lote_id');
    }

    // --- Scopes ---

    public function scopeProximosACaducar($query, int $dias = 30)
    {
        return $query->whereNotNull('fecha_caducidad')
            ->whereDate('fecha_caducidad', '<=', now()->addDays($dias))
            ->where('estado', 'disponible');
    }
}
