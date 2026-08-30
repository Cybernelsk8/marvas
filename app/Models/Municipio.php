<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Municipio extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'nombre',
        'departamento_id'
    ];

    public function departamento() : BelongsTo {
        return $this->belongsTo(Departamento::class);
    }
}
