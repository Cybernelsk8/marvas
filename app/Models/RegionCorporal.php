<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegionCorporal extends Model
{
    use HasFactory, Searchable;

    protected $table = 'regiones_corporales';

    protected $fillable = [
        'codigo',
        'nombre',
        'vista',
        'tiene_lateralidad',
        'trazos',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'tiene_lateralidad' => 'boolean',
            'trazos' => 'array',
            'activo' => 'boolean',
        ];
    }

    public function zonasDolor(): HasMany
    {
        return $this->hasMany(ZonaDolor::class, 'region_id');
    }
}
