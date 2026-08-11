<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformeSistema extends Model
{
    use HasFactory;

    protected $table = 'informe_sistemas';

    const UPDATED_AT = null;

    protected $fillable = [
        'consulta_id',
        'sistema_nervioso',
        'sistema_digestivo',
        'sistema_respiratorio',
        'sistema_urinario',
        'sistema_endocrino',
        'sistema_musculoesqueletico',
        'sistema_ginecologico',
        'piel_faneras',
        'observaciones_generales',
    ];

    protected function casts(): array
    {
        return [
            'sistema_nervioso' => 'array',
            'sistema_digestivo' => 'array',
            'sistema_respiratorio' => 'array',
            'sistema_urinario' => 'array',
            'sistema_endocrino' => 'array',
            'sistema_musculoesqueletico' => 'array',
            'sistema_ginecologico' => 'array',
            'piel_faneras' => 'array',
        ];
    }

    // --- Relaciones ---

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }
}
