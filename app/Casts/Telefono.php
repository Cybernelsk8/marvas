<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Teléfono de 8 dígitos guardado y mostrado como ####-####.
 * Ej.: set('48840150') guarda '4884-0150'; get('4884-0150') devuelve '4884-0150'.
 * Un valor que no tenga 8 dígitos se conserva tal cual (la validación del formulario lo rechaza).
 */
class Telefono implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : formatTelefono((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return blank($value) ? null : formatTelefono(trim((string) $value));
    }
}
