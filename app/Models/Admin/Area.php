<?php

namespace App\Models\Admin;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use Searchable, SoftDeletes;
    public $timestamps = false;

    protected $fillable = [
        'name',
        'area_id',
    ];

    public function dependency(){
        return $this->belongsTo(Area::class, 'area_id');
    }
}
