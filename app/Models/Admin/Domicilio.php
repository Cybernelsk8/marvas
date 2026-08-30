<?php

namespace App\Models\Admin;

use App\Models\Municipio;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domicilio extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'municipio_id',
        'zona_id',
        'colonia',
        'direccion',
        'user_information_id',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function user_information(): BelongsTo
    {
        return $this->belongsTo(UserInformation::class, 'user_information_id');
    }
}
