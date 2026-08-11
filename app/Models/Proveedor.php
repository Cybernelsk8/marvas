<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre_comercial',
        'razon_social',
        'rfc_nit',
        'contacto_nombre',
        'telefono',
        'email',
        'direccion',
        'notas',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function productos(): HasMany
    {
        return $this->hasMany(ProductoInventario::class, 'proveedor_principal_id');
    }

    public function lotesInventario(): HasMany
    {
        return $this->hasMany(LoteInventario::class);
    }

    public function ordenesCompra(): HasMany
    {
        return $this->hasMany(OrdenCompra::class);
    }
}
