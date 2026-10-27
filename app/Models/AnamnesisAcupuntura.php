<?php

namespace App\Models;

use App\Support\AcupunctureCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnamnesisAcupuntura extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'anamnesis_acupuntura';

    protected $fillable = [
        'anamnesis_id',
        'diagnostico_energetico',
        'lengua_forma',
        'lengua_color',
        'lengua_otros_rasgos',
        'saburra_color',
        'saburra_espesor',
        'saburra_distribucion',
        'humedad',
        'lengua_movimiento',
        'pulsos',
        'diagnostico_8_reglas',
        'diagnostico_6_niveles',
        'diagnostico_4_capas',
        'organos_zang_fu',
        'principio_terapeutico',
        'puntos_sugeridos',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'lengua_forma' => 'array',
            'pulsos' => 'array',
            'lengua_color' => 'array',
            'lengua_otros_rasgos' => 'array',
            'saburra_color' => 'array',
            'saburra_espesor' => 'array',
            'saburra_distribucion' => 'array',
            'diagnostico_8_reglas' => 'array',
            'diagnostico_6_niveles' => 'array',
            'diagnostico_4_capas' => 'array',
            'organos_zang_fu' => 'array',
            'puntos_sugeridos' => 'array',
        ];
    }

    // --- Relaciones ---

    public function anamnesis(): BelongsTo
    {
        return $this->belongsTo(AnamnesisGeneral::class, 'anamnesis_id');
    }

    // --- Helpers ---

    /** Bloque del protocolo por categoría (distal|local|maestro): ['puntos', 'tecnica', 'observaciones']. */
    public function puntosPorCategoria(string $categoria): array
    {
        return $this->puntos_sugeridos[$categoria] ?? [];
    }

    /** Elemento, generación y control de los órganos Zang-Fu afectados. */
    public function relacionesZangFu(): array
    {
        return AcupunctureCatalog::relaciones($this->organos_zang_fu ?? []);
    }
}
