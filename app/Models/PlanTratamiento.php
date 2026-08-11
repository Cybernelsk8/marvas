<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanTratamiento extends Model
{
    use HasFactory;

    protected $table = 'planes_tratamiento';

    protected $fillable = [
        'expediente_id',
        'clasificacion_id',
        'diagnostico_integrativo',
        'terapias_indicadas',
        'frecuencia_semanal',
        'numero_sesiones',
        'indicaciones_casa',
        'observaciones',
        'fase_actual',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'terapias_indicadas' => 'array',
            'frecuencia_semanal' => 'integer',
            'numero_sesiones' => 'integer',
            'activo' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(ExpedienteMaestro::class, 'expediente_id');
    }

    public function clasificacion(): BelongsTo
    {
        return $this->belongsTo(ClasificacionPaciente::class, 'clasificacion_id');
    }

    public function paquetesPaciente(): HasMany
    {
        return $this->hasMany(PaquetePaciente::class, 'plan_id');
    }
}
