<?php

namespace App\Support;

/**
 * Catálogos de la evaluación de acupuntura
 * (recursos IA/MARVAS_especificacion_anamnesis_acupuntura_mapa_dolor.md, Parte B, hoja `Acu`).
 *
 * Los campos multi-selección se guardan como arreglo de claves; pulsos, 8 reglas y protocolo
 * se guardan como JSON con subcampos de texto o polos elegidos.
 */
class AcupunctureCatalog
{
    public const MOVIMIENTOS_LENGUA = [
        'normal' => 'Normal',
        'rigida' => 'Rígida',
        'temblorosa' => 'Temblorosa',
        'debil' => 'Débil',
        'desviada' => 'Desviada',
        'retraida' => 'Retraída',
    ];

    public const HUMEDADES = ['seca' => 'Seca', 'humeda' => 'Húmeda', 'pegajosa' => 'Pegajosa'];

    // Sugerencias para la calidad del pulso; el campo es texto libre (el Excel no define catálogo).
    public const CALIDADES_PULSO = [
        'Normal', 'Vacío', 'Lleno', 'Rápido', 'Lento', 'Tenso (cuerda)', 'Resbaladizo', 'Superficial', 'Profundo',
    ];

    // 12 posiciones de pulso: clave => [etiqueta, órgano que corresponde].
    public static function pulsos(): array
    {
        return [
            'cun_der_superficial' => ['Cun derecho superficial', 'Intestino grueso'],
            'cun_der_profundo' => ['Cun derecho profundo', 'Pulmón'],
            'guan_der_superficial' => ['Guan derecho superficial', 'Estómago'],
            'guan_der_profundo' => ['Guan derecho profundo', 'Bazo-páncreas'],
            'chi_der_superficial' => ['Chi derecho superficial', 'Triple recalentador'],
            'chi_der_profundo' => ['Chi derecho profundo', 'Maestro corazón'],
            'cun_izq_superficial' => ['Cun izquierdo superficial', 'Intestino delgado'],
            'cun_izq_profundo' => ['Cun izquierdo profundo', 'Corazón'],
            'guan_izq_superficial' => ['Guan izquierdo superficial', 'Vesícula biliar'],
            'guan_izq_profundo' => ['Guan izquierdo profundo', 'Hígado'],
            'chi_izq_superficial' => ['Chi izquierdo superficial', 'Vejiga'],
            'chi_izq_profundo' => ['Chi izquierdo profundo', 'Riñón'],
        ];
    }

    // Campos de selección múltiple: campo => [clave => etiqueta].
    public static function multiOptions(): array
    {
        return [
            'lengua_forma' => [
                'normal' => 'Normal', 'delgada' => 'Delgada', 'hinchada' => 'Hinchada',
                'dentada' => 'Dentada (marcas de dientes)', 'puntiaguda' => 'Puntiaguda',
                'rajada' => 'Rajada o agrietada', 'deforme' => 'Deforme',
            ],
            'lengua_color' => [
                'palida' => 'Pálida', 'roja' => 'Roja', 'roja_intensa' => 'Roja intensa',
                'purpura' => 'Púrpura', 'azulada' => 'Azulada', 'manchas' => 'Manchas visibles',
            ],
            'lengua_otros_rasgos' => [
                'puntos_rojos' => 'Puntos rojos', 'ulceras' => 'Úlceras',
                'venas_sublinguales' => 'Venas sublinguales prominentes',
            ],
            'saburra_color' => [
                'blanca' => 'Blanca', 'amarilla' => 'Amarilla', 'grisacea' => 'Grisácea',
                'marron' => 'Marrón', 'ausente' => 'Ausente',
            ],
            'saburra_espesor' => ['delgada' => 'Delgada', 'gruesa' => 'Gruesa'],
            'saburra_distribucion' => ['uniforme' => 'Uniforme', 'irregular' => 'Irregular'],
            'diagnostico_6_niveles' => self::niveles(),
            'diagnostico_4_capas' => [
                'qi_defensivo' => 'Qi defensivo (IG / P)',
                'qi_energetico' => 'Qi energético (Riñón / Vejiga)',
                'qi_nutritivo' => 'Qi nutritivo (Bazo-P / ID / Estómago)',
                'qi_xue' => 'Qi Xue, sangre (Corazón / Hígado)',
            ],
            'organos_zang_fu' => array_map(fn (array $organ): string => $organ[0], self::organos()),
        ];
    }

    // 6 niveles con la nomenclatura estándar (corrige las erratas del Excel).
    public static function niveles(): array
    {
        return [
            'mano_yin_tai_yin' => 'Mano Yin · Tai Yin (Pulmón)',
            'mano_yin_jue_yin' => 'Mano Yin · Jue Yin (Maestro corazón)',
            'mano_yin_shao_yin' => 'Mano Yin · Shao Yin (Corazón)',
            'pie_yin_tai_yin' => 'Pie Yin · Tai Yin (Bazo-P)',
            'pie_yin_jue_yin' => 'Pie Yin · Jue Yin (Hígado)',
            'pie_yin_shao_yin' => 'Pie Yin · Shao Yin (Riñón)',
            'mano_yang_yang_ming' => 'Mano Yang · Yang Ming (Intestino grueso)',
            'mano_yang_shao_yang' => 'Mano Yang · Shao Yang (Triple recalentador)',
            'mano_yang_tai_yang' => 'Mano Yang · Tai Yang (Intestino delgado)',
            'pie_yang_yang_ming' => 'Pie Yang · Yang Ming (Estómago)',
            'pie_yang_shao_yang' => 'Pie Yang · Shao Yang (Vesícula biliar)',
            'pie_yang_tai_yang' => 'Pie Yang · Tai Yang (Vejiga)',
        ];
    }

    // 8 reglas: eje => [etiqueta, [polo => etiqueta]]; se elige un polo por eje.
    public static function ochoReglas(): array
    {
        return [
            'localizacion' => ['Localización', ['interno' => 'Interno', 'externo' => 'Externo']],
            'temperatura' => ['Temperatura', ['frio' => 'Frío', 'calor' => 'Calor']],
            'fuerza' => ['Fuerza', ['vacio' => 'Vacío', 'plenitud' => 'Plenitud']],
            'polaridad' => ['Polaridad', ['yin' => 'Yin', 'yang' => 'Yang']],
        ];
    }

    // Bloques del protocolo; cada uno guarda puntos, técnica y observaciones (inferido, por confirmar con el terapeuta).
    public static function protocolo(): array
    {
        return [
            'distal' => 'Puntos distales',
            'local' => 'Puntos locales',
            'maestro' => 'Puntos maestro',
        ];
    }

    // Órganos Zang-Fu: clave => [etiqueta, elemento].
    public static function organos(): array
    {
        return [
            'higado' => ['Hígado', 'madera'],
            'vesicula_biliar' => ['Vesícula biliar', 'madera'],
            'corazon' => ['Corazón', 'fuego'],
            'intestino_delgado' => ['Intestino delgado', 'fuego'],
            'maestro_corazon' => ['Maestro corazón', 'fuego'],
            'triple_recalentador' => ['Triple recalentador', 'fuego'],
            'bazo_pancreas' => ['Bazo-páncreas', 'tierra'],
            'estomago' => ['Estómago', 'tierra'],
            'pulmon' => ['Pulmón', 'metal'],
            'intestino_grueso' => ['Intestino grueso', 'metal'],
            'rinon' => ['Riñón', 'agua'],
            'vejiga' => ['Vejiga', 'agua'],
        ];
    }

    // Elementos con su ciclo Sheng (generación): elemento => [etiqueta, madre, controlador (Ke)].
    public static function elementos(): array
    {
        return [
            'madera' => ['Madera', 'agua', 'metal'],
            'fuego' => ['Fuego', 'madera', 'agua'],
            'tierra' => ['Tierra', 'fuego', 'madera'],
            'metal' => ['Metal', 'tierra', 'fuego'],
            'agua' => ['Agua', 'metal', 'tierra'],
        ];
    }

    /**
     * Calcula elemento, generación (madre) y control de cada órgano afectado.
     * Ej.: relaciones(['higado']) => [['organo' => 'Hígado', 'elemento' => 'Madera',
     *      'generacion' => 'Agua', 'control' => 'Metal']]
     */
    public static function relaciones(array $selected): array
    {
        $organos = self::organos();
        $elementos = self::elementos();
        $rows = [];

        foreach ($selected as $key) {
            if (! isset($organos[$key])) {
                continue;
            }

            [$label, $element] = $organos[$key];
            [$elementLabel, $mother, $controller] = $elementos[$element];
            $rows[] = [
                'organo' => $label,
                'elemento' => $elementLabel,
                'generacion' => $elementos[$mother][0],
                'control' => $elementos[$controller][0],
            ];
        }

        return $rows;
    }

    // Valores iniciales de los campos estructurados de acupuntura.
    public static function defaults(): array
    {
        $multi = array_map(fn (): array => [], self::multiOptions());

        return [
            ...$multi,
            'pulsos' => array_map(fn (): array => ['calidad' => ''], self::pulsos()),
            'diagnostico_8_reglas' => array_fill_keys(array_keys(self::ochoReglas()), ''),
            'puntos_sugeridos' => array_map(
                fn (): array => ['puntos' => '', 'tecnica' => '', 'observaciones' => ''],
                self::protocolo(),
            ),
        ];
    }

    // true si el campo pertenece a los bloques estructurados.
    public static function isStructured(string $field): bool
    {
        return array_key_exists($field, self::defaults());
    }

    // Combina lo guardado con los valores por defecto y descarta claves desconocidas.
    public static function merge(string $field, mixed $stored): mixed
    {
        $default = self::defaults()[$field];

        if (! is_array($stored)) {
            return $default;
        }

        if (isset(self::multiOptions()[$field])) {
            return array_values(array_intersect(array_keys(self::multiOptions()[$field]), $stored));
        }

        foreach ($default as $key => $value) {
            if (is_array($value)) {
                foreach (array_keys($value) as $sub) {
                    if (isset($stored[$key][$sub])) {
                        $default[$key][$sub] = (string) $stored[$key][$sub];
                    }
                }
            } elseif (isset($stored[$key])) {
                $default[$key] = (string) $stored[$key];
            }
        }

        return $default;
    }

    // Reglas de validación del campo estructurado con claves explícitas.
    public static function rules(string $prefix, string $field): array
    {
        $path = "{$prefix}.{$field}";

        if (isset(self::multiOptions()[$field])) {
            return [
                $path => ['array'],
                "{$path}.*" => ['string', 'in:'.implode(',', array_keys(self::multiOptions()[$field]))],
            ];
        }

        $rules = [];

        match ($field) {
            'pulsos' => collect(array_keys(self::pulsos()))->each(
                function (string $key) use (&$rules, $path): void {
                    $rules["{$path}.{$key}.calidad"] = ['nullable', 'string', 'max:60'];
                },
            ),
            'diagnostico_8_reglas' => collect(self::ochoReglas())->each(
                function (array $axis, string $key) use (&$rules, $path): void {
                    $rules["{$path}.{$key}"] = ['nullable', 'in:'.implode(',', array_keys($axis[1]))];
                },
            ),
            default => collect(array_keys(self::protocolo()))->each(
                function (string $key) use (&$rules, $path): void {
                    $rules["{$path}.{$key}.puntos"] = ['nullable', 'string', 'max:500'];
                    $rules["{$path}.{$key}.tecnica"] = ['nullable', 'string', 'max:500'];
                    $rules["{$path}.{$key}.observaciones"] = ['nullable', 'string', 'max:2000'];
                },
            ),
        };

        return $rules;
    }

    // Deja null un campo sin capturar y los textos vacíos como null; así no se crean filas vacías.
    public static function normalize(string $field, array $value): ?array
    {
        if (isset(self::multiOptions()[$field])) {
            return $value === [] ? null : array_values($value);
        }

        $flat = fn (mixed $item): array => is_array($item) ? $item : [$item];
        $filled = false;
        foreach ($value as $key => $item) {
            foreach ($flat($item) as $sub => $text) {
                if (filled($text)) {
                    $filled = true;
                } elseif (is_array($item)) {
                    $value[$key][$sub] = null;
                } else {
                    $value[$key] = null;
                }
            }
        }

        return $filled ? $value : null;
    }
}
