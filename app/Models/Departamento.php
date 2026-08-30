<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'nombre'
    ];

    public function municipios() : HasMany {
        return $this->hasMany(Municipio::class);
    }
}
