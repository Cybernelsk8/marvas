<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentimientoRegistro extends Model
{
    use HasFactory;

    protected $table = 'consentimientos_registro';

    public $timestamps = false;

    protected $fillable = [
        'expediente_id',
        'consulta_id',
        'plantilla_id',
        'profesional_id',
        'confirma_lectura_fisica',
        'confirma_resolucion_dudas',
        'acepta_voluntariamente',
        'firma_digital_path',
        'huella_dactilar_path',
        'ip_registro',
        'dispositivo_info',
        'pdf_comprobante_path',
        'aceptado_at',
    ];

    protected function casts(): array
    {
        return [
            'confirma_lectura_fisica' => 'boolean',
            'confirma_resolucion_dudas' => 'boolean',
            'acepta_voluntariamente' => 'boolean',
            'aceptado_at' => 'datetime',
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

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(ConsentimientoPlantilla::class, 'plantilla_id');
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class, 'profesional_id');
    }
}
