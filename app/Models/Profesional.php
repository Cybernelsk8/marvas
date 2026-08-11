<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profesional extends Model
{
    use HasFactory;

    protected $table = 'profesionales';

    protected $fillable = [
        'user_id',
        'nombre_completo',
        'cedula_profesional',
        'color_agenda_hex',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(Especialidad::class, 'profesional_especialidades')
            ->withPivot('es_principal');
    }

    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class, 'profesional_sucursales')
            ->withPivot('es_principal');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    public function evolucionesClinicas(): HasMany
    {
        return $this->hasMany(EvolucionClinica::class);
    }

    public function consentimientosRegistrados(): HasMany
    {
        return $this->hasMany(ConsentimientoRegistro::class, 'profesional_id');
    }

    // --- Helpers ---

    /**
     * Especialidad marcada como principal en el pivote (evita duplicar el dato
     * en una columna propia, ver decisión de diseño: especialidad_principal
     * se eliminó de esta tabla).
     */
    public function especialidadPrincipal(): ?Especialidad
    {
        return $this->especialidades->firstWhere('pivot.es_principal', true);
    }

    public function sucursalPrincipal(): ?Sucursal
    {
        return $this->sucursales->firstWhere('pivot.es_principal', true);
    }
}
