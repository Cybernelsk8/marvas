<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tipo por defecto
    |--------------------------------------------------------------------------
    | Si un header no define 'type', se asume este. Así ningún componente
    | existente se rompe (retrocompatibilidad total).
    */
    'default_type' => 'string',

    /*
    |--------------------------------------------------------------------------
    | Familias
    |--------------------------------------------------------------------------
    | Una família define el COMPORTAMIENTO de búsqueda: qué operadores son
    | válidos y cómo participa (o no) en el buscador de texto libre.
    | Varios "types" pueden compartir la misma família (ver abajo) para no
    | repetir la misma lista de operadores en cada tipo.
    |
    | 'searchable_global':
    |   true          -> siempre se incluye en el OR del buscador general
    |   'numeric_only'-> solo se incluye si el término escrito es is_numeric()
    |   'date_only'   -> solo se incluye si el término matchea patrón de fecha
    |   false          -> nunca participa en el buscador general (solo en filtros)
    */
    'families' => [

        'string' => [
            'operators' => ['like', 'not like', '=', '!=', 'null', 'not null'],
            'searchable_global' => true,
        ],

        'numeric' => [
            'operators' => ['=', '!=', '>', '<', '>=', '<=', 'between', 'not between', 'in', 'not in', 'null', 'not null'],
            'searchable_global' => 'numeric_only',
        ],

        'date' => [
            'operators' => ['=', '!=', '>', '<', '>=', '<=', 'between', 'not between', 'null', 'not null'],
            'searchable_global' => 'date_only',
        ],

        'phone' => [
            'operators' => ['=', 'like', 'null', 'not null'],
            'searchable_global' => true,
        ],

        'boolean' => [
            'operators' => ['=', '!=', 'null', 'not null'],
            'searchable_global' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos
    |--------------------------------------------------------------------------
    | Lo que el programador escribe en $headers[]['type']. Cada uno apunta a
    | una família (comportamiento de búsqueda) y trae sus propias opciones de
    | formato visual (usadas por maskFormatVal en la vista). Agregar un tipo
    | nuevo NO requiere tocar Searchable ni DataTable, solo agregarlo aquí.
    */
    'types' => [

        'string' => [
            'family' => 'string',
        ],

        'numeric' => [
            'family' => 'numeric',
            'format' => ['decimals' => 0],
        ],

        'decimal' => [
            'family' => 'numeric',
            'format' => ['decimals' => 2],
        ],

        'currency' => [
            'family' => 'numeric',
            'format' => ['decimals' => 2, 'symbol' => 'Q', 'currency' => 'GTQ'],
        ],

        'currency_usd' => [
            'family' => 'numeric',
            'format' => ['decimals' => 2, 'symbol' => '$', 'currency' => 'USD'],
        ],

        'percentage' => [
            'family' => 'numeric',
            'format' => ['decimals' => 2, 'suffix' => '%'],
        ],

        'date' => [
            'family' => 'date',
            'format' => ['pattern' => 'd-m-Y'],
        ],

        'datetime' => [
            'family' => 'date',
            'format' => ['pattern' => 'd-m-Y H:i:s'],
        ],

        'time' => [
            'family' => 'date',
            'format' => ['pattern' => 'H:i:s'],
        ],

        'phone' => [
            'family' => 'phone',
            'format' => ['separator' => ' - ', 'groups' => [4, 4]],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Grupos de operadores (solo para agrupar visualmente en el <select>)
    |--------------------------------------------------------------------------
    | No afecta qué operadores son válidos por campo (eso lo decide la
    | família); solo decide bajo qué <optgroup> aparece cada operador cuando
    | se pinta la lista filtrada de un campo.
    */
    'operator_groups' => [
        '='           => 'Comparación',
        '!='          => 'Comparación',
        '>'           => 'Comparación',
        '<'           => 'Comparación',
        '>='          => 'Comparación',
        '<='          => 'Comparación',
        'like'        => 'Texto',
        'not like'    => 'Texto',
        'between'     => 'Rango',
        'not between' => 'Rango',
        'in'          => 'Lista',
        'not in'      => 'Lista',
        'null'        => 'Nulos',
        'not null'    => 'Nulos',
    ],

    /*
    |--------------------------------------------------------------------------
    | Opciones globales de formato
    |--------------------------------------------------------------------------
    | Equivalen a las $defaultOptions actuales dentro de maskFormatVal().
    | maskFormatVal() se ajustará para leer de aquí en vez de tener sus
    | propios valores hardcodeados por separado.
    */
    'locale' => 'es_GT',
    'timezone' => 'America/Guatemala',
];
