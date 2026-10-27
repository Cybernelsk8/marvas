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
        'consulta_id',
        'estado',
        'eva_maximo',
        'zona_mayor_dolor',
        'observaciones',
    ];

    protected function casts(): array
    {
        return ['eva_maximo' => 'integer'];
    }

    // --- Relaciones ---

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    public function zonasDolor(): HasMany
    {
        return $this->hasMany(ZonaDolor::class, 'mapa_id');
    }
}
