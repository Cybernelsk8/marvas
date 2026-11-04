<?php

namespace App\Models;

use App\Casts\Telefono;
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
            'telefono' => Telefono::class,
            'telefono_emergencia' => Telefono::class,
        ];
    }

    /** Estados civiles disponibles en todos los formularios. Ej.: Paciente::estadosCiviles()[0] // 'Soltero' */
    public const ESTADOS_CIVILES = ['Soltero', 'Casado', 'Divorciado', 'Viudo', 'Unión libre', 'Otro'];

    /** Religiones disponibles en todos los formularios. Ej.: Paciente::religiones() */
    public const RELIGIONES = [
        'Católico', 'Protestante', 'Evangélico', 'Ortodoxo', 'Musulmán', 'Judío', 'Budista', 'Ninguna', 'Otro',
    ];

    /** Lista de estados civiles para selects y reglas `Rule::in()`. */
    public static function estadosCiviles(): array
    {
        return self::ESTADOS_CIVILES;
    }

    /** Lista de religiones para selects y reglas `Rule::in()`. */
    public static function religiones(): array
    {
        return self::RELIGIONES;
    }

    /** Regla de validación de teléfono de 8 dígitos, con o sin guion. Ej.: '4884-0150' o '48840150'. */
    public const REGLA_TELEFONO = 'regex:/^\d{4}-?\d{4}$/';

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
