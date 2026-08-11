<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function profesionales(): BelongsToMany
    {
        return $this->belongsToMany(Profesional::class, 'profesional_sucursales')
            ->withPivot('es_principal');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    public function lotesInventario(): HasMany
    {
        return $this->hasMany(LoteInventario::class);
    }

    public function stockSucursal(): HasMany
    {
        return $this->hasMany(StockSucursal::class);
    }
}
