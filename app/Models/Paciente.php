<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paciente extends Model
{
    use HasFactory;

    protected $table = 'pacientes';

    protected $fillable = [
        'codigo_expediente',
        'nombre',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'estado_civil',
        'religion',
        'numero_dpi',
        'telefono',
        'telefono_emergencia',
        'email',
        'direccion',
        'ocupacion',
        'como_nos_conocio',
        'foto_path',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    // --- Accesores ---

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellidos}");
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
}
