<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZonaDolor extends Model
{
    use HasFactory;

    protected $table = 'zonas_dolor';

    protected $fillable = [
        'mapa_id',
        'region_id',
        'lateralidad',
        'eva',
        'nivel',
        'tipos',
        'irradiacion',
        'inicio',
        'patron',
        'comentario',
    ];

    protected function casts(): array
    {
        return [
            'eva' => 'integer',
            'tipos' => 'array',
            'irradiacion' => 'array',
            'patron' => 'array',
        ];
    }

    // Deriva el nivel de dolor desde el EVA. Ej.: nivelDesdeEva(7) => 'intenso'.
    public static function nivelDesdeEva(int $eva): string
    {
        return match (true) {
            $eva <= 0 => 'sin_dolor',
            $eva <= 3 => 'leve',
            $eva <= 6 => 'moderado',
            $eva <= 8 => 'intenso',
            default => 'muy_intenso',
        };
    }

    // Mantiene `nivel` siempre coherente con `eva`.
    protected static function booted(): void
    {
        static::saving(function (self $zona) {
            $zona->nivel = self::nivelDesdeEva((int) $zona->eva);
        });
    }

    // --- Relaciones ---

    public function mapa(): BelongsTo
    {
        return $this->belongsTo(MapaCorporal::class, 'mapa_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(RegionCorporal::class, 'region_id');
    }
}
