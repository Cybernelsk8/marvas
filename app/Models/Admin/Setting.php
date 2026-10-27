<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Setting extends Model
{
    const TYPES = ['string', 'integer', 'boolean', 'array', 'date'];

    protected $fillable = [
        'key',
        'value',
        'description',
        'type',
    ];

    protected function value(): Attribute
    {
        return Attribute::make(
            // GET: Cuando accedes a $setting->valor
            get: function (mixed $value) {
                if (is_null($value)) {
                    return null;
                }

                return match ($this->type) {
                    'integer' => (int) $value,
                    'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                    'array' => is_string($value) ? json_decode($value, true) : $value,
                    'date' => Carbon::parse($value)->format('Y-m-d'),
                    default => (string) $value, // 'string' o cualquier otro
                };
            },
            // SET: Cuando asignas $setting->valor = $algo
            set: function (mixed $value) {
                return match ($this->type) {
                    'array' => is_array($value) ? json_encode($value) : $value,
                    'date' => $value instanceof Carbon ? $value->format('Y-m-d') : $value,
                    default => $value,
                };
            }
        );
    }
}
