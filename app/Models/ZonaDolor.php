<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZonaDolor extends Model
{
    use HasFactory;

    protected $table = 'zonas_dolor';

    public $timestamps = false;

    protected $fillable = [
        'mapa_id',
        'zona',
        'lateralidad',
        'intensidad',
        'tipo_dolor',
        'descripcion',
        'coord_x',
        'coord_y',
    ];

    protected function casts(): array
    {
        return [
            'intensidad' => 'integer',
            'coord_x' => 'decimal:2',
            'coord_y' => 'decimal:2',
        ];
    }

    /**
     * tipo_dolor es un SET de MariaDB (no un tipo nativo de Eloquent, a
     * diferencia de JSON no hay cast automático). Se expone como array de
     * PHP y se guarda como lista separada por comas, tal como MariaDB
     * almacena internamente un SET.
     */
    protected function tipoDolor(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? explode(',', $value) : [],
            set: fn ($value) => is_array($value) ? implode(',', $value) : $value,
        );
    }

    // --- Relaciones ---

    public function mapa(): BelongsTo
    {
        return $this->belongsTo(MapaCorporal::class, 'mapa_id');
    }
}
