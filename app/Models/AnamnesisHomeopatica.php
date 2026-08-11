<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnamnesisHomeopatica extends Model
{
    use HasFactory;

    protected $table = 'anamnesis_homeopatica';

    protected $fillable = [
        'anamnesis_id',
        'aspecto_paciente',
        'sintomas_locales_generales',
        'sintomas_mentales',
        'sintomas_biopatograficos',
        'diagnostico_posible',
        'tratamiento_indicado',
        'observaciones',
    ];

    // --- Relaciones ---

    public function anamnesis(): BelongsTo
    {
        return $this->belongsTo(AnamnesisGeneral::class, 'anamnesis_id');
    }
}
