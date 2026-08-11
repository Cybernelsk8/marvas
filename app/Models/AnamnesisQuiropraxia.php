<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnamnesisQuiropraxia extends Model
{
    use HasFactory;

    protected $table = 'anamnesis_quiropraxia';

    protected $fillable = [
        'anamnesis_id',
        'cirugias',
        'fracturas',
        'hospitalizaciones',
        'implantes_metales',
        'detalle_implantes',
        'embarazo',
        'semanas_gestacion',
        'adormecimientos',
        'zonas_adormecidas',
        'ciatica',
        'dolor_cabeza',
        'vertigo',
        'atm',
        'detalle_atm',
        'postura',
        'calidad_sueno',
        'desplazamiento',
        'tiempo_trabajo',
        'recreacion',
        'diagnostico',
        'plan_seguimiento_inicial',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'implantes_metales' => 'boolean',
            'embarazo' => 'boolean',
            'semanas_gestacion' => 'integer',
            'adormecimientos' => 'boolean',
            'ciatica' => 'boolean',
            'dolor_cabeza' => 'boolean',
            'vertigo' => 'boolean',
            'atm' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function anamnesis(): BelongsTo
    {
        return $this->belongsTo(AnamnesisGeneral::class, 'anamnesis_id');
    }
}
