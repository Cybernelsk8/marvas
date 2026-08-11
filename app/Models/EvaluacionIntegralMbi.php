<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluacionIntegralMbi extends Model
{
    use HasFactory;

    protected $table = 'evaluacion_integral_mbi';

    protected $fillable = [
        'consulta_id',
        'integracion_clinica',
        'sistemas_comprometidos',
        'hallazgos_principales',
        'objetivo_terapeutico',
        'observaciones_generales',
        'especialidad_principal',
        'especialidades_complementarias',
        'modelo_6plus_seleccion',
    ];

    protected function casts(): array
    {
        return [
            'sistemas_comprometidos' => 'array',
            'especialidades_complementarias' => 'array',
            'modelo_6plus_seleccion' => 'array',
        ];
    }

    // --- Relaciones ---

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    public function clasificaciones(): HasMany
    {
        return $this->hasMany(ClasificacionPaciente::class, 'evaluacion_id');
    }
}
