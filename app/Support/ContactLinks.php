<?php

namespace App\Support;

/**
 * Construye enlaces públicos de contacto a partir de los datos de las sedes
 * y de la configuración del sitio (`config/marvas.php`).
 *
 * Centraliza el formato de teléfonos, WhatsApp y Google Maps para que la
 * landing y el footer no dupliquen la lógica de armado de URLs.
 */
final class ContactLinks
{
    /**
     * Devuelve solo los dígitos de un teléfono.
     *
     * @param  string|null  $phone  Teléfono tal como está guardado (p. ej. "5862-3352").
     * @return string|null Dígitos sin separadores, o null si no hay número.
     */
    // Devuelve solo los dígitos de un teléfono.
    //   Ejemplo: ContactLinks::digits('5862-3352') → '58623352'.
    //   Resultado: null cuando el valor es nulo o no contiene dígitos.
    public static function digits(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return filled($digits) ? $digits : null;
    }

    /**
     * Devuelve el teléfono internacional listo para un enlace `tel:` o `wa.me`.
     *
     * @param  string|null  $phone  Teléfono local de la sede.
     * @return string|null Número con código de país, o null si no hay número.
     */
    // Devuelve el teléfono internacional listo para un enlace tel: o wa.me.
    //   Ejemplo: con MARVAS_COUNTRY_CODE=502, '5862-3352' → '50258623352'.
    //   Resultado: si el número ya incluye el código de país, se conserva tal cual.
    public static function internationalDigits(?string $phone): ?string
    {
        $digits = self::digits($phone);

        if ($digits === null) {
            return null;
        }

        $countryCode = self::digits((string) config('marvas.country_code'));

        if ($countryCode !== null && strlen($digits) === 8) {
            return $countryCode.$digits;
        }

        return $digits;
    }

    /**
     * Construye el enlace telefónico para marcar desde el navegador.
     *
     * @param  string|null  $phone  Teléfono de la sede.
     * @return string|null Enlace `tel:` o null si no hay número.
     */
    // Construye el enlace telefónico para marcar desde el navegador.
    //   Desde Blade: <a href="{{ ContactLinks::phoneLink($sede->telefono) }}">
    //   Resultado: 'tel:+50258623352' o null cuando la sede no tiene teléfono.
    public static function phoneLink(?string $phone): ?string
    {
        $number = self::internationalDigits($phone);

        return $number === null ? null : 'tel:+'.$number;
    }

    /**
     * Construye un enlace de WhatsApp con mensaje precargado.
     *
     * @param  string|null  $phone  Teléfono de la sede o número global.
     * @param  string|null  $message  Mensaje inicial; por defecto se usa el configurado.
     * @return string|null Enlace `wa.me` o null si no hay número.
     */
    // Construye un enlace de WhatsApp con mensaje precargado.
    //   Ejemplo: ContactLinks::whatsapp('5862-3352')
    //   Resultado: https://wa.me/50258623352?text=Hola...
    public static function whatsapp(?string $phone, ?string $message = null): ?string
    {
        $number = self::internationalDigits($phone);

        if ($number === null) {
            return null;
        }

        $message ??= (string) config('marvas.whatsapp_message');
        $query = filled($message) ? '?text='.rawurlencode($message) : '';

        return 'https://wa.me/'.$number.$query;
    }

    /**
     * Construye la URL del mapa embebido de Google Maps para una dirección.
     *
     * @param  string|null  $address  Dirección escrita de la sede.
     * @return string|null URL para un iframe, o null si no hay dirección.
     */
    // Construye la URL del mapa embebido de Google Maps para una dirección.
    //   Desde Blade: <iframe src="{{ ContactLinks::mapEmbed($sede->direccion) }}">
    //   Resultado: https://www.google.com/maps?q=...&z=16&output=embed
    public static function mapEmbed(?string $address): ?string
    {
        $address = trim((string) $address);

        if ($address === '') {
            return null;
        }

        return 'https://www.google.com/maps?q='.rawurlencode($address).'&z=16&output=embed';
    }

    /**
     * Construye la URL de indicaciones de Google Maps hacia una dirección.
     *
     * @param  string|null  $address  Dirección escrita de la sede.
     * @return string|null URL de indicaciones, o null si no hay dirección.
     */
    // Construye la URL de indicaciones de Google Maps hacia una dirección.
    //   Desde Blade: <a href="{{ ContactLinks::directions($sede->direccion) }}">
    //   Resultado: https://www.google.com/maps/dir/?api=1&destination=...
    public static function directions(?string $address): ?string
    {
        $address = trim((string) $address);

        if ($address === '') {
            return null;
        }

        return 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($address);
    }
}
