<?php

namespace App\Models\Admin;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use Searchable, SoftDeletes;

    const type = [
        'parent' => 'Padre',
        'child' => 'Hijo',
    ];

    public $timestamps = false;
    protected $fillable = [
        'label',
        'icon',
        'route',
        'order',
        'page_id',
        'type',
        'permission_name'
    ];


    public function parent()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function children()
    {
        return $this->hasMany(Page::class, 'page_id');
    }

    public function getorderLabelAttribute()
    {
        if ($this->type == 'parent') {
            return $this->order;
        } else {
            return $this->parent?->order . '.' . $this->order;
        }
    }
}
