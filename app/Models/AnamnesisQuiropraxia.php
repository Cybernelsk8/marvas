<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnamnesisQuiropraxia extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'anamnesis_quiropraxia';

    protected $fillable = [
        'anamnesis_id',
        'sintomas',
        'postura_movilidad',
        'pruebas_neuro_ortopedicas',
        'otras_pruebas',
        'indice_funcional',
        'seguridad',
        'decision_manipulacion',
        'motivo_decision',
        'diagnostico',
        'plan_seguimiento_inicial',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'sintomas' => 'array',
            'postura_movilidad' => 'array',
            'pruebas_neuro_ortopedicas' => 'array',
            'indice_funcional' => 'array',
            'seguridad' => 'array',
        ];
    }

    // Criterios de 06D marcados como "Sí"; si hay alguno se debe advertir antes de manipular. Ej.: ['trauma_fractura']
    public function alertasSeguridad(): array
    {
        return collect($this->seguridad ?? [])
            ->filter(fn (array $item): bool => ($item['estado'] ?? null) === 'si')
            ->keys()
            ->all();
    }

    // --- Relaciones ---

    public function anamnesis(): BelongsTo
    {
        return $this->belongsTo(AnamnesisGeneral::class, 'anamnesis_id');
    }
}
