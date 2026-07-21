<?php

namespace App\Support\DataTable;

use Illuminate\Support\Str;

/**
 * Convierte el array crudo `$headers` de un componente Livewire en un mapa
 * normalizado que usan tanto DataTable (trait de UI/estado) como Searchable
 * (trait de ejecución de queries). Ningún otro sitio debe volver a recorrer
 * `$headers` a mano — todos leen de aquí.
 */
class HeaderMap
{
    /** Memoiza por request: si DataTable y Searchable lo piden en el mismo
     *  ciclo con los mismos $headers, no se recalcula. */
    protected static array $cache = [];

    protected array $entries;

    protected function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    public static function build(array $headers): self
    {
        $key = md5(serialize($headers));

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $excludedIndexes = ['actions', 'checkbox', 'action', 'options', 'selection', 'active'];
        // Subconjunto de lo anterior que es CASI SEGURO virtual (no columnas
        // reales de BD): botones de acción, checkbox de selección, menú de
        // opciones. 'active' se deja fuera a propósito porque suele ser una
        // columna real — si en tu caso no lo es, márcala con 'virtual' => true
        // en el header y aquí se respeta.
        $definitelyVirtual = ['actions', 'checkbox', 'action', 'selection'];
        $defaultType     = config('datatable.default_type', 'string');

        $entries = collect($headers)
            ->filter(fn($header) => isset($header['index']))
            ->map(function ($header) use ($excludedIndexes, $definitelyVirtual, $defaultType) {
                $index   = $header['index'];
                $isCount = str_ends_with($index, '_count');

                // Un campo *_count es numérico por naturaleza (se compara con
                // >, <, =...) salvo que el programador diga otra cosa explícitamente.
                $type = $header['type'] ?? ($isCount ? 'numeric' : $defaultType);

                $typeConfig   = config("datatable.types.{$type}") ?? config("datatable.types.{$defaultType}") ?? [];
                $family       = $typeConfig['family'] ?? 'string';
                $familyConfig = config("datatable.families.{$family}", []);

                $isRelation = Str::contains($index, '.');
                $isExcluded = !empty($header['exclude']) || in_array($index, $excludedIndexes) || $isCount;
                $isVirtual  = !empty($header['virtual']) || in_array($index, $definitelyVirtual);

                return [
                    'index'             => $index,
                    'label'             => $header['label'] ?? $index,
                    'type'              => $type,
                    'family'            => $family,
                    'format'            => $typeConfig['format'] ?? [],
                    'operators'         => $familyConfig['operators'] ?? [],
                    // true | 'numeric_only' | 'date_only' | false
                    'searchable_global' => $familyConfig['searchable_global'] ?? false,
                    'is_relation'       => $isRelation,
                    // 'a.b.c' -> path 'a.b', columna 'c' (soporta relaciones anidadas)
                    'relation_path'     => $isRelation ? Str::beforeLast($index, '.') : null,
                    'relation_column'   => $isRelation ? Str::afterLast($index, '.') : null,
                    'is_count'          => $isCount,
                    'count_relation'    => $isCount ? Str::beforeLast($index, '_count') : null,
                    // is_excluded: fuera del dropdown de filtros/orden.
                    'is_excluded'       => $isExcluded,
                    // is_virtual: no es una columna real de BD (no debe pedirse en SELECT).
                    'is_virtual'        => $isVirtual,
                    'raw'               => $header,
                ];
            })
            ->values()
            ->keyBy('index')
            ->all();

        return self::$cache[$key] = new self($entries);
    }

    // ===================== Consultas para DataTable (UI) =====================

    /** Índices visibles/filtrables (reemplaza getAvailableFields / getAvailableHeaders) */
    public function availableFields(): array
    {
        return collect($this->entries)
            ->reject(fn($e) => $e['is_excluded'])
            ->pluck('index')
            ->values()
            ->all();
    }

    /** Headers crudos completos (label, align, class...) ya filtrados, para pintar el <select> de campo */
    public function availableHeaders(): array
    {
        return collect($this->entries)
            ->reject(fn($e) => $e['is_excluded'])
            ->pluck('raw')
            ->values()
            ->all();
    }

    /** Operadores válidos para UN campo específico (idea 2: dropdown dinámico) */
    public function operatorsFor(string $field): array
    {
        return $this->entries[$field]['operators'] ?? [];
    }

    // =================== Consultas para Searchable (ejecución) ===================

    /** Índices que participan en filtros avanzados (excluye actions/_count/etc.) */
    public function searchableFields(): array
    {
        return $this->availableFields();
    }

    /**
     * Índices que deben incluirse en el buscador de texto libre para UN
     * término específico. La decisión "¿este campo numérico/fecha aplica a
     * lo que el usuario escribió?" vive aquí, no en Searchable — Searchable
     * solo recibe la lista final y arma el WHERE.
     */
    public function fieldsForSearchTerm(string $term): array
    {
        return collect($this->entries)
            ->reject(fn($e) => $e['is_excluded'])
            ->filter(function ($e) use ($term) {
                return match ($e['searchable_global']) {
                    true           => true,
                    'numeric_only' => is_numeric($term),
                    'date_only'    => $this->isDateTerm($term),
                    default        => false,
                };
            })
            ->pluck('index')
            ->values()
            ->all();
    }

    /** Detecta si un término escrito por el usuario tiene forma de fecha (YYYY-MM-DD...) */
    protected function isDateTerm(string $term): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?$/', $term);
    }

    /** Relaciones para eager load, en notación de punto completa (soporta anidamiento real) */
    public function eagerLoads(): array
    {
        return collect($this->entries)
            ->filter(fn($e) => $e['is_relation'])
            ->pluck('relation_path')
            ->unique()
            ->values()
            ->all();
    }

    /** Relaciones para withCount, extraídas de columnas *_count */
    public function withCounts(): array
    {
        return collect($this->entries)
            ->filter(fn($e) => $e['is_count'])
            ->pluck('count_relation')
            ->unique()
            ->values()
            ->all();
    }

    public function indexes(): array
    {
        return array_keys($this->entries);
    }

    public function get(string $field): ?array
    {
        return $this->entries[$field] ?? null;
    }

    public function all(): array
    {
        return $this->entries;
    }

    /**
     * Columnas reales de tabla que conviene seleccionar (evita SELECT *).
     *
     * OJO — esto SOLO considera los headers directos (no relación, no _count).
     * Si tienes columnas que la vista necesita pero que NO están en $headers
     * (por ejemplo una columna 'active' que usas en un badge sin declararla
     * como header), no aparecerán aquí y la consulta fallará si las usas en
     * la vista. $extraColumns existe para cubrir justamente esos casos —
     * pásale ahí cualquier columna real que necesites y no esté en headers.
     */
    public function selectableColumns(string $table, string $primaryKey = 'id', array $extraColumns = []): array
    {
        $columns = collect($this->entries)
            ->reject(fn($e) => $e['is_relation'] || $e['is_count'] || $e['is_virtual'])
            ->pluck('index')
            ->merge($extraColumns)
            ->push($primaryKey)
            ->unique()
            ->values();

        return $columns->map(fn($c) => "{$table}.{$c}")->all();
    }

    /** Todos los operadores existentes en el sistema (unión de todas las familias).
     *  Útil para validar estructuras de filtro sin depender de un $headers puntual. */
    public static function allOperators(): array
    {
        return collect(config('datatable.families', []))
            ->pluck('operators')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }
}
