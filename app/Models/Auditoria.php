<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditoria';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'tabla_afectada',
        'registro_id',
        'accion',
        'datos_anteriores',
        'datos_nuevos',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
        ];
    }

    // --- Relaciones ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
