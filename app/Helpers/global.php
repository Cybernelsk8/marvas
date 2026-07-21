<?php

use App\Services\Captcha;
use App\Services\Jwt\HashToken;

if (! function_exists('getNestedValue')) {
    function getNestedValue($array, $key)
    {
        $keys = explode('.', $key);
        foreach ($keys as $innerKey) {
            if (isset($array[$innerKey])) {
                $array = $array[$innerKey];
            } else {
                return null;
            }
        }

        return $array;
    }
}

if (! function_exists('hasChanged')) {
    function hasChanged($target, $source): bool
    {

        if ($target === $source) {
            return false;
        }

        if (! is_array($target) || ! is_array($source)) {
            return $target !== $source;
        }

        if (count($target) !== count($source)) {
            return true;
        }

        foreach ($target as $key => $value) {

            if (! array_key_exists($key, $source) || hasChanged($value, $source[$key])) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('hashToken')) {
    function hashToken()
    {
        return new HashToken;
    }
}

if (! function_exists('captchaGenerate')) {
    function captchaGenerate(string $text)
    {
        $captcha = new Captcha;

        return $captcha->generate($text);
    }
}

if (! function_exists('formatTelefono')) {
    function formatTelefono(?string $value)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        $cleaned = preg_replace('/\D/', '', $value);

        if (strlen($cleaned) === 8) {
            return substr($cleaned, 0, 4) . '-' . substr($cleaned, 4, 4);
        }

        return $value;
    }
}

if (! function_exists('dateToHour')) {
    function dateToHour(string $fechaDestino)
    {
        $ahora = new DateTime;
        $destino = new DateTime($fechaDestino);

        $diferencia = $destino->getTimestamp() - $ahora->getTimestamp();
        $horas = $diferencia / 3600;

        return round($horas, 2);
    }
}

if (! function_exists('maskFormatVal')) {
    function maskFormatVal(mixed $value, $type = 'default', $options = [])
    {
        // Validar valor nulo o vacío
        if ($value === null || $value === '') {
            return '';
        }

        // Opciones de formato del tipo, tomadas de config/datatable.php — la
        // misma definición que usan HeaderMap y Searchable para saber cómo
        // buscar este campo. Si agregas un tipo nuevo al config pero no le
        // agregas su 'case' aquí abajo, cae en 'default' (valor crudo sin formatear).
        $typeFormat = config("datatable.types.{$type}.format", []);

        // Opciones por defecto (sirven de respaldo si el tipo no trae 'format' en el config)
        $defaultOptions = [
            'locale' => config('datatable.locale', 'es_GT'),
            'currency' => $typeFormat['currency'] ?? 'GTQ',
            'dateFormat' => $typeFormat['pattern'] ?? 'd-m-Y',
            'datetimeFormat' => $typeFormat['pattern'] ?? 'd-m-Y H:i:s',
            'timeFormat' => $typeFormat['pattern'] ?? 'H:i:s',
            'phoneSeparator' => $typeFormat['separator'] ?? ' - ',
            'phoneGroups' => $typeFormat['groups'] ?? [4, 4],
            'decimals' => $typeFormat['decimals'] ?? 2,
            'percentageDecimals' => $typeFormat['decimals'] ?? 2,
            'timezone' => config('datatable.timezone', 'America/Guatemala'),
        ];

        $options = array_merge($defaultOptions, $options);

        // Configurar locale para formato numérico
        setlocale(LC_NUMERIC, $options['locale'] . '.UTF-8');

        $result = '';

        switch ($type) {
            case 'numeric':
                // Formato numérico con separadores de miles
                $result = number_format($value, 0, '.', ',');
                break;

            case 'decimal':
                // Formato numérico con decimales
                $result = number_format($value, $options['decimals'], '.', ',');
                break;

            case 'currency':
                // Formato de moneda
                $formatted = number_format($value, $options['decimals'], '.', ',');
                $currencySymbol = $typeFormat['symbol'] ?? ($options['currency'] === 'GTQ' ? 'Q' : $options['currency']);
                $result = $currencySymbol . ' ' . $formatted;
                break;

            case 'currency_usd':
                // Formato específico USD
                $formatted = number_format($value, $options['decimals'], '.', ',');
                $currencySymbol = $typeFormat['symbol'] ?? '$';
                $result = $currencySymbol . ' ' . $formatted;
                break;

            case 'percentage':
                // Formato porcentaje
                $formatted = number_format($value, $options['percentageDecimals'], '.', ',');
                $suffix = $typeFormat['suffix'] ?? '%';
                $result = $formatted . $suffix;
                break;

            case 'date':
                // Formato de fecha
                try {
                    $date = new DateTime($value);
                    if ($options['timezone']) {
                        $date->setTimezone(new DateTimeZone($options['timezone']));
                    }
                    $result = $date->format($options['dateFormat']);
                } catch (Exception $e) {
                    $result = '';
                }
                break;

            case 'datetime':
                // Formato de fecha y hora
                try {
                    $datetime = new DateTime($value);
                    if ($options['timezone']) {
                        $datetime->setTimezone(new DateTimeZone($options['timezone']));
                    }
                    $result = $datetime->format($options['datetimeFormat']);
                } catch (Exception $e) {
                    $result = '';
                }
                break;

            case 'time':
                // Formato de hora
                try {
                    $time = new DateTime($value);
                    if ($options['timezone']) {
                        $time->setTimezone(new DateTimeZone($options['timezone']));
                    }
                    $result = $time->format($options['timeFormat']);
                } catch (Exception $e) {
                    $result = '';
                }
                break;

            case 'phone':

                $cleaned = preg_replace('/\D/', '', $value);

                if (strlen($cleaned) === 8) {
                    $result = substr($cleaned, 0, 4) . '-' . substr($cleaned, 4, 4);
                }

                break;

            default:
                $result = $value;
                break;
        }

        return $result;
    }
}
