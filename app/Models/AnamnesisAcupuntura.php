<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnamnesisAcupuntura extends Model
{
    use HasFactory;

    protected $table = 'anamnesis_acupuntura';

    protected $fillable = [
        'anamnesis_id',
        'diagnostico_energetico',
        'pulso_cun_der_superficial',
        'pulso_cun_der_profundo',
        'pulso_guan_der_superficial',
        'pulso_guan_der_profundo',
        'pulso_chi_der_superficial',
        'pulso_chi_der_profundo',
        'pulso_cun_izq_superficial',
        'pulso_cun_izq_profundo',
        'pulso_guan_izq_superficial',
        'pulso_guan_izq_profundo',
        'pulso_chi_izq_superficial',
        'pulso_chi_izq_profundo',
        'lengua_forma',
        'lengua_color',
        'lengua_otros_rasgos',
        'saburra_color',
        'saburra_espesor',
        'saburra_distribucion',
        'humedad',
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

    /** Puntos filtrados por categoría: distal|local|maestro. */
    public function puntosPorCategoria(string $categoria): array
    {
        return collect($this->puntos_sugeridos ?? [])
            ->where('categoria', $categoria)
            ->values()
            ->all();
    }
}
