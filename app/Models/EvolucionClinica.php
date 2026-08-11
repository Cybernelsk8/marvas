<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvolucionClinica extends Model
{
    use HasFactory;

    protected $table = 'evolucion_clinica';

    protected $fillable = [
        'consulta_id',
        'especialidad_id',
        'profesional_id',
        'hallazgos',
        'tratamiento_aplicado',
        'respuesta_paciente',
        'proxima_conducta',
        'necesita_cambio_plan',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'hallazgos' => 'array',
            'necesita_cambio_plan' => 'boolean',
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

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class);
    }
}
