<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent no soporta llaves primarias compuestas de forma nativa, así que
 * se sobreescriben setKeysForSaveQuery()/setKeysForSelectQuery() para que
 * save()/refresh() funcionen correctamente sobre (producto_id, sucursal_id).
 * Para buscar un registro usar siempre StockSucursal::where(...)->first(),
 * NUNCA StockSucursal::find() (asume PK simple autoincremental).
 */
class StockSucursal extends Model
{
    use HasFactory;

    protected $table = 'stock_sucursal';

    public $incrementing = false;

    const CREATED_AT = null;

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'producto_id',
        'sucursal_id',
        'cantidad_actual',
        'stock_minimo',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_actual' => 'integer',
            'stock_minimo' => 'integer',
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

    // --- Soporte de llave compuesta ---

    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('producto_id', $this->getAttribute('producto_id'))
            ->where('sucursal_id', $this->getAttribute('sucursal_id'));
    }

    protected function setKeysForSelectQuery($query)
    {
        return $this->setKeysForSaveQuery($query);
    }

    // --- Helpers ---

    /**
     * Ajusta el stock de forma atómica (transacción + lockForUpdate) para
     * evitar condiciones de carrera entre varios cajeros/sucursales
     * escribiendo al mismo tiempo. $delta positivo = entrada, negativo = salida.
     */
    public static function ajustar(int $productoId, int $sucursalId, int $delta): self
    {
        return DB::transaction(function () use ($productoId, $sucursalId, $delta) {
            $stock = self::where('producto_id', $productoId)
                ->where('sucursal_id', $sucursalId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = self::create([
                    'producto_id' => $productoId,
                    'sucursal_id' => $sucursalId,
                    'cantidad_actual' => 0,
                    'stock_minimo' => 0,
                ]);
            }

            $stock->cantidad_actual = max(0, $stock->cantidad_actual + $delta);
            $stock->save();

            return $stock;
        });
    }
}
