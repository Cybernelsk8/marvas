<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AnamnesisGeneral extends Model
{
    use HasFactory;

    protected $table = 'anamnesis_generales';

    protected $fillable = [
        'consulta_id',
        'especialidad_id',
        'motivo_consulta',
        'historia_padecimiento',
        'antecedentes_patologicos',
        'antecedentes_quirurgicos',
        'antecedentes_traumaticos',
        'alergias',
        'medicamentos_actuales',
        'habitos',
        'sueno',
        'alimentacion',
        'estado_emocional',
        'presion_arterial',
        'frecuencia_cardiaca',
        'frecuencia_respiratoria',
        'temperatura',
        'glicemia',
        'peso_kg',
        'talla_cm',
        // 'imc' es columna generada (STORED), no se asigna manualmente.
        'datos_formulario',
        'observaciones_profesional',
    ];

    protected function casts(): array
    {
        return [
            'antecedentes_patologicos' => 'array',
            'habitos' => 'array',
            'datos_formulario' => 'array',
            'frecuencia_cardiaca' => 'integer',
            'frecuencia_respiratoria' => 'integer',
            'temperatura' => 'decimal:1',
            'glicemia' => 'integer',
            'peso_kg' => 'decimal:2',
            'talla_cm' => 'decimal:2',
            'imc' => 'decimal:2',
        ];
    }

    // --- Relaciones ---

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }

    public function antecedentesFamiliaresDetalle(): HasMany
    {
        return $this->hasMany(AntecedenteFamiliarDetalle::class, 'anamnesis_id');
    }

    public function homeopatica(): HasOne
    {
        return $this->hasOne(AnamnesisHomeopatica::class, 'anamnesis_id');
    }

    public function quiropraxia(): HasOne
    {
        return $this->hasOne(AnamnesisQuiropraxia::class, 'anamnesis_id');
    }

    public function acupuntura(): HasOne
    {
        return $this->hasOne(AnamnesisAcupuntura::class, 'anamnesis_id');
    }

    public function mapasCorporales(): HasMany
    {
        return $this->hasMany(MapaCorporal::class, 'anamnesis_id');
    }
}
