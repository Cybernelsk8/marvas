<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivoClinico extends Model
{
    use HasFactory;

    protected $table = 'archivos_clinicos';

    const UPDATED_AT = null;

    protected $fillable = [
        'expediente_id',
        'consulta_id',
        'subido_por',
        'tipo_archivo',
        'nombre_original',
        'path_almacenamiento',
        'mime_type',
        'tamano_bytes',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'tamano_bytes' => 'integer',
        ];
    }

    // --- Relaciones ---

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(ExpedienteMaestro::class, 'expediente_id');
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
