<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Consulta extends Model
{
    use HasFactory;

    protected $table = 'consultas';

    protected $fillable = [
        'expediente_id',
        'sucursal_id',
        'especialidad_id',
        'profesional_id',
        'cita_id',
        'fecha_hora',
        'estado',
        'observaciones',
        'numero_consulta',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'numero_consulta' => 'integer',
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

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class);
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function anamnesisGeneral(): HasOne
    {
        return $this->hasOne(AnamnesisGeneral::class, 'consulta_id');
    }

    public function evaluacionIntegral(): HasOne
    {
        return $this->hasOne(EvaluacionIntegralMbi::class, 'consulta_id');
    }

    public function informeSistemas(): HasOne
    {
        return $this->hasOne(InformeSistema::class, 'consulta_id');
    }

    public function testIntoxicacion(): HasOne
    {
        return $this->hasOne(TestIntoxicacion::class, 'consulta_id');
    }

    public function evolucionesClinicas(): HasMany
    {
        return $this->hasMany(EvolucionClinica::class, 'consulta_id');
    }

    public function consentimientoRegistro(): HasOne
    {
        return $this->hasOne(ConsentimientoRegistro::class, 'consulta_id');
    }

    public function archivosClinicos(): HasMany
    {
        return $this->hasMany(ArchivoClinico::class, 'consulta_id');
    }

    public function consumosProductos(): HasMany
    {
        return $this->hasMany(ConsumoProductoConsulta::class, 'consulta_id');
    }

    public function mapasCorporales(): HasMany
    {
        return $this->hasMany(MapaCorporal::class, 'consulta_id');
    }

    // --- Helpers ---

    /**
     * Calcula el siguiente número de consulta del paciente en esta
     * especialidad. Debe llamarse dentro de una transacción con lock sobre
     * el expediente para evitar condiciones de carrera (ver decisión de
     * diseño: numero_consulta no tiene garantía a nivel de base de datos).
     */
    public static function siguienteNumero(int $expedienteId, int $especialidadId): int
    {
        return static::query()
            ->where('expediente_id', $expedienteId)
            ->where('especialidad_id', $especialidadId)
            ->lockForUpdate()
            ->max('numero_consulta') + 1;
    }
}
