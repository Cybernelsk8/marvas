<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Especialidad extends Model
{
    use HasFactory;

    protected $table = 'especialidades';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'color_hex',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function profesionales(): BelongsToMany
    {
        return $this->belongsToMany(Profesional::class, 'profesional_especialidades')
            ->withPivot('es_principal');
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(ProductoInventario::class);
    }

    public function evolucionesClinicas(): HasMany
    {
        return $this->hasMany(EvolucionClinica::class);
    }

    public function anamnesisGenerales(): HasMany
    {
        return $this->hasMany(AnamnesisGeneral::class);
    }
}
