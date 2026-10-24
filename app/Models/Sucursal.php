<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sucursal extends Model
{
    use HasFactory, Searchable;

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

    public function evaluadorMbi(): HasOne
    {
        return $this->hasOne(SucursalEvaluadorMbi::class);
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
