<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentimientoPlantilla extends Model
{
    use HasFactory;

    protected $table = 'consentimientos_plantilla';

    const UPDATED_AT = null;

    protected $fillable = [
        'version',
        'titulo',
        'descripcion_terapias',
        'terapias_cubiertas',
        'vigente_desde',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'terapias_cubiertas' => 'array',
            'vigente_desde' => 'date',
            'activo' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function registros(): HasMany
    {
        return $this->hasMany(ConsentimientoRegistro::class, 'plantilla_id');
    }
}
