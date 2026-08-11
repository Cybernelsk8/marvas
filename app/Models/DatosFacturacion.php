<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatosFacturacion extends Model
{
    use HasFactory;

    protected $table = 'datos_facturacion';

    protected $fillable = [
        'paciente_id',
        'nit',
        'nombre_razon_social',
        'direccion_fiscal',
        'es_predeterminado',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'es_predeterminado' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
}
