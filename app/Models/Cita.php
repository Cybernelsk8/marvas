<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';

    protected $fillable = [
        'expediente_id',
        'sucursal_id',
        'profesional_id',
        'especialidad_id',
        'paquete_paciente_id',
        'agendada_por',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'duracion_minutos',
        'tipo_cita',
        'estado',
        'motivo_cancelacion',
        'notas_recepcion',
        'recordatorio_enviado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora_inicio' => 'datetime',
            'fecha_hora_fin' => 'datetime',
            'duracion_minutos' => 'integer',
            'recordatorio_enviado' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(ExpedienteMaestro::class, 'expediente_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class);
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }

    public function paquetePaciente(): BelongsTo
    {
        return $this->belongsTo(PaquetePaciente::class, 'paquete_paciente_id');
    }

    public function agendadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agendada_por');
    }

    public function consulta(): HasOne
    {
        return $this->hasOne(Consulta::class, 'cita_id');
    }

    // --- Helpers de negocio ---

    /**
     * MariaDB no soporta exclusion constraints: esta es la regla que evita
     * citas traslapadas para el mismo profesional. Llamar SIEMPRE dentro de
     * una transacción antes de crear/actualizar una cita.
     */
    public static function existeTraslape(
        int $profesionalId,
        \DateTimeInterface $inicio,
        \DateTimeInterface $fin,
        ?int $ignorarCitaId = null,
    ): bool {
        return static::query()
            ->where('profesional_id', $profesionalId)
            ->whereNotIn('estado', ['cancelada', 'no_asistio'])
            ->when($ignorarCitaId, fn ($q) => $q->whereKeyNot($ignorarCitaId))
            ->where('fecha_hora_inicio', '<', $fin)
            ->where('fecha_hora_fin', '>', $inicio)
            ->lockForUpdate()
            ->exists();
    }
}
