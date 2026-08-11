<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapaCorporal extends Model
{
    use HasFactory;

    protected $table = 'mapas_corporales';

    protected $fillable = [
        'anamnesis_id',
        'consulta_id',
        'imagen_path',
        'observaciones',
    ];

    // --- Relaciones ---

    public function anamnesis(): BelongsTo
    {
        return $this->belongsTo(AnamnesisGeneral::class, 'anamnesis_id');
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    public function zonasDolor(): HasMany
    {
        return $this->hasMany(ZonaDolor::class, 'mapa_id');
    }
}
