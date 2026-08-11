<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntecedenteFamiliarDetalle extends Model
{
    use HasFactory;

    protected $table = 'antecedentes_familiares_detalle';

    public $timestamps = false;

    protected $fillable = [
        'anamnesis_id',
        'parentesco',
        'enfermedad',
        'presente',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'presente' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function anamnesis(): BelongsTo
    {
        return $this->belongsTo(AnamnesisGeneral::class, 'anamnesis_id');
    }
}
