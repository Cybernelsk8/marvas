<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paciente extends Model
{
    use HasFactory, Searchable;

    protected $table = 'pacientes';

    protected $fillable = [
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'estado_civil',
        'religion',
        'numero_dpi',
        'telefono',
        'contacto_emergencia',
        'telefono_emergencia',
        'email',
        'direccion',
        'ocupacion',
        'como_nos_conocio',
        'foto_path',
        'activo',
        'fusionado_en_paciente_id',
    ];

    protected $appends = [
        'nombre_completo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function getAccessorMap(): array
    {
        return [
            'nombre_completo' => ['nombres', 'apellidos'],
        ];
    }

    // --- Accesores ---
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    // Edad en años calculada desde fecha_nacimiento; null si no hay fecha. Ej.: $paciente->edad // 34
    public function getEdadAttribute(): ?int
    {
        return $this->fecha_nacimiento?->age;
    }

    // --- Relaciones ---

    public function expediente(): HasOne
    {
        return $this->hasOne(ExpedienteMaestro::class);
    }

    public function datosFacturacion(): HasMany
    {
        return $this->hasMany(DatosFacturacion::class);
    }

    public function fusionadoEn(): BelongsTo
    {
        return $this->belongsTo(self::class, 'fusionado_en_paciente_id');
    }

    public function pacientesFusionados(): HasMany
    {
        return $this->hasMany(self::class, 'fusionado_en_paciente_id');
    }
}
