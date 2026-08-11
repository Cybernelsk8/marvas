<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClasificacionPaciente extends Model
{
    use HasFactory;

    protected $table = 'clasificacion_paciente';

    public $timestamps = false;

    protected $fillable = [
        'expediente_id',
        'evaluacion_id',
        'categoria',
        'justificacion',
        'objetivo_caso',
        'fase_inicial',
        'clasificado_por',
        'clasificado_at',
    ];

    protected function casts(): array
    {
        return [
            'clasificado_at' => 'datetime',
        ];
    }

    // --- Relaciones ---

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(ExpedienteMaestro::class, 'expediente_id');
    }

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(EvaluacionIntegralMbi::class, 'evaluacion_id');
    }

    public function clasificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clasificado_por');
    }

    public function planesTratamiento(): HasMany
    {
        return $this->hasMany(PlanTratamiento::class, 'clasificacion_id');
    }
}
