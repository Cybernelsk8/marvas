<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaInventario extends Model
{
    use HasFactory;

    protected $table = 'categorias_inventario';

    protected $fillable = [
        'nombre',
        'slug',
        'tipo',
        'descripcion',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function productos(): HasMany
    {
        return $this->hasMany(ProductoInventario::class, 'categoria_id');
    }
}
