<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpedienteMaestro extends Model
{
    use HasFactory;

    protected $table = 'expediente_maestro';

    protected $fillable = [
        'paciente_id',
        'clasificacion',
        'objetivo_principal',
        'fase_terapeutica',
        'estado',
        'notas_generales',
    ];

    // --- Relaciones ---

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'expediente_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'expediente_id');
    }

    public function clasificaciones(): HasMany
    {
        return $this->hasMany(ClasificacionPaciente::class, 'expediente_id');
    }

    public function planesTratamiento(): HasMany
    {
        return $this->hasMany(PlanTratamiento::class, 'expediente_id');
    }

    public function paquetesPaciente(): HasMany
    {
        return $this->hasMany(PaquetePaciente::class, 'expediente_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'expediente_id');
    }

    public function archivosClinicos(): HasMany
    {
        return $this->hasMany(ArchivoClinico::class, 'expediente_id');
    }

    public function consentimientosRegistrados(): HasMany
    {
        return $this->hasMany(ConsentimientoRegistro::class, 'expediente_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'expediente_id');
    }

    // --- Helpers ---

    /**
     * La clasificación vigente es la más reciente (no hay bandera "activa"
     * en clasificacion_paciente, ver decisión de diseño original).
     */
    public function clasificacionVigente(): ?ClasificacionPaciente
    {
        return $this->clasificaciones()->latest('clasificado_at')->first();
    }

    public function planTratamientoVigente(): ?PlanTratamiento
    {
        return $this->planesTratamiento()->where('activo', true)->latest()->first();
    }
}
