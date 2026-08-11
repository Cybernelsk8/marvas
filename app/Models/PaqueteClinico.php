<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaqueteClinico extends Model
{
    use HasFactory;

    protected $table = 'paquetes_clinicos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_clasificacion',
        'numero_sesiones',
        'precio_base',
        'especialidades_json',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'numero_sesiones' => 'integer',
            'precio_base' => 'decimal:2',
            'especialidades_json' => 'array',
            'activo' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function paquetesPaciente(): HasMany
    {
        return $this->hasMany(PaquetePaciente::class, 'paquete_id');
    }
}
