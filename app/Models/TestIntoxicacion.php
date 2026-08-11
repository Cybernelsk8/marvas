<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestIntoxicacion extends Model
{
    use HasFactory;

    protected $table = 'test_intoxicacion';

    const UPDATED_AT = null;

    protected $fillable = [
        'consulta_id',
        'bloques',
        'puntaje_total',
        'interpretacion',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'bloques' => 'array',
            'puntaje_total' => 'integer',
        ];
    }

    // --- Relaciones ---

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }
}
