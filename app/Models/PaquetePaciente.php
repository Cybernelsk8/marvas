<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaquetePaciente extends Model
{
    use HasFactory;

    protected $table = 'paquetes_paciente';

    protected $fillable = [
        'expediente_id',
        'paquete_id',
        'plan_id',
        'sesiones_totales',
        'sesiones_usadas',
        'precio_acordado',
        'saldo_pendiente',
        'estado',
        'notas',
        'inicio_at',
        'vencimiento_at',
    ];

    protected function casts(): array
    {
        return [
            'sesiones_totales' => 'integer',
            'sesiones_usadas' => 'integer',
            'precio_acordado' => 'decimal:2',
            'saldo_pendiente' => 'decimal:2',
            'inicio_at' => 'date',
            'vencimiento_at' => 'date',
        ];
    }

    // --- Relaciones ---

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(ExpedienteMaestro::class, 'expediente_id');
    }

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(PaqueteClinico::class, 'paquete_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanTratamiento::class, 'plan_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'paquete_paciente_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'paquete_paciente_id');
    }

    // --- Helpers ---

    public function getSesionesRestantesAttribute(): int
    {
        return max(0, $this->sesiones_totales - $this->sesiones_usadas);
    }
}
