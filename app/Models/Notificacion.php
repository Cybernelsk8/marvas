<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    const UPDATED_AT = null;

    protected $fillable = [
        'expediente_id',
        'cita_id',
        'usuario_id',
        'tipo',
        'canal',
        'estado',
        'contenido',
        'programado_at',
        'enviado_at',
        'intento',
    ];

    protected function casts(): array
    {
        return [
            'programado_at' => 'datetime',
            'enviado_at' => 'datetime',
            'intento' => 'integer',
        ];
    }

    // --- Relaciones ---

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(ExpedienteMaestro::class, 'expediente_id');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
