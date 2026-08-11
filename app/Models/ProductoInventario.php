<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoInventario extends Model
{
    use HasFactory;

    protected $table = 'productos_inventario';

    protected $fillable = [
        'categoria_id',
        'proveedor_principal_id',
        'especialidad_id',
        'sku',
        'nombre',
        'descripcion',
        'unidad_medida',
        'requiere_lote',
        'costo_promedio',
        'precio_venta',
        'es_vendible',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'requiere_lote' => 'boolean',
            'es_vendible' => 'boolean',
            'activo' => 'boolean',
            'costo_promedio' => 'decimal:2',
            'precio_venta' => 'decimal:2',
        ];
    }

    // --- Relaciones ---

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaInventario::class, 'categoria_id');
    }

    public function proveedorPrincipal(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_principal_id');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }

    public function lotesInventario(): HasMany
    {
        return $this->hasMany(LoteInventario::class, 'producto_id');
    }

    public function stockSucursal(): HasMany
    {
        return $this->hasMany(StockSucursal::class, 'producto_id');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }

    public function ordenesCompraDetalle(): HasMany
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'producto_id');
    }

    public function consumosEnConsultas(): HasMany
    {
        return $this->hasMany(ConsumoProductoConsulta::class, 'producto_id');
    }

    // --- Helpers ---

    /** Stock disponible en una sucursal específica (no existe columna global). */
    public function stockEn(int $sucursalId): int
    {
        return $this->stockSucursal()
            ->where('sucursal_id', $sucursalId)
            ->value('cantidad_actual') ?? 0;
    }
}
