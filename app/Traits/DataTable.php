<?php

namespace App\Traits;

use App\Support\DataTable\HeaderMap;
use Illuminate\Support\Str;
use Livewire\WithPagination;

trait DataTable
{
    use WithPagination;

    // ========== PROPIEDADES PÚBLICAS DEL TRAIT ==========
    public string $search = '';
    public string $sortBy = 'id';
    public string $sortDirection = 'desc';

    public int $per_page = 10;
    public array $filters = [['field' => '', 'operator' => '', 'value' => '']];
    public array $selectedRows = [];

    // ========== CATEGORÍAS DE SINTAXIS DE OPERADOR ==========
    // Nota: esto es distinto de las "familias" en config/datatable.php.
    // Las familias deciden qué operadores son VÁLIDOS para un tipo de campo.
    // Estas listas de aquí solo describen cómo se debe INTERPRETAR el valor
    // crudo que escribió el usuario según el operador elegido (independiente
    // del tipo de campo), así que no tiene sentido moverlas al config.
    protected array $arrayOperators = ['between', 'not between', 'in', 'not in'];
    protected array $numericOperators = ['=', '!=', '>', '<', '>=', '<='];
    protected array $stringOperators = ['like', 'not like'];
    protected array $nullOperators = ['null', 'not null'];

    protected array $queryString = [
        'search'        => ['except' => ''],
        'sortBy'        => ['except' => 'id'],
        'sortDirection' => ['except' => 'desc'],
        'per_page'      => ['except' => 10],
        'filters'       => ['except' => [['field' => '', 'operator' => '', 'value' => '']]],
    ];

    // ========== INICIALIZACIÓN ==========
    public function bootDataTable(): void
    {
        $this->initializeFilters();
    }

    // ========== MÉTODOS PÚBLICOS PARA VISTAS ==========

    /**
     * Ordena por columna específica
     */
    public function sort(string $column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Agrega un nuevo filtro vacío
     */
    public function addFilter(): void
    {
        $this->filters[] = ['field' => '', 'operator' => '', 'value' => ''];
    }

    /**
     * Elimina un filtro específico
     */
    public function deleteFilter(int $index): void
    {
        if ($index === 0 && count($this->filters) === 1) {
            $this->filters[0] = ['field' => '', 'operator' => '', 'value' => ''];
        } else {
            unset($this->filters[$index]);
            $this->filters = array_values($this->filters);
        }

        $this->resetPage();
    }

    /**
     * Limpia todos los filtros
     */
    public function clearFilters(): void
    {
        $this->resetPage();
        $this->filters = [['field' => '', 'operator' => '', 'value' => '']];
    }

    /**
     * Limpia búsqueda + filtros juntos — pensado para el botón del estado
     * vacío ("no se encontraron resultados").
     */
    public function resetAllFilters(): void
    {
        $this->search = '';
        $this->clearFilters();
    }

    // ========== MÉTODOS DE PROCESAMIENTO ==========

    /**
     * Procesa los filtros convirtiendo valores según el operador
     */
    protected function processFilters(): array
    {
        return collect($this->filters)
            ->filter(function ($filter) {
                $operator = strtolower($filter['operator'] ?? '');

                if (empty($filter['field']) || empty($operator)) {
                    return false;
                }

                // Operadores que no requieren valor
                $noValueOperators = ['null', 'not null'];
                if (in_array($operator, $noValueOperators)) {
                    return true;
                }

                // Los demás operadores requieren valor no vacío
                return isset($filter['value']) && $filter['value'] !== '';
            })
            ->map(function ($filter) {
                $operator = strtolower($filter['operator']);
                $value = $filter['value'] ?? '';

                if (in_array($operator, $this->arrayOperators) && is_string($value) && !empty($value)) {
                    $filter['value'] = $this->convertToArray($value);

                    if (in_array($operator, ['between', 'not between'])) {
                        if (is_array($filter['value']) && count($filter['value']) !== 2) {
                            $filter['value'] = [];
                        }
                    }
                }

                if (in_array($operator, $this->nullOperators)) {
                    $filter['value'] = null;
                }

                if (in_array($operator, ['like', 'not like']) && is_string($value) && !empty($value)) {
                    if (!Str::contains($value, '%')) {
                        $filter['value'] = "%{$value}%";
                    }
                }

                return $filter;
            })
            ->values()
            ->toArray();
    }

    /**
     * Convierte string separado por comas en array
     */
    protected function convertToArray(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn($item) => trim($item))
            ->filter(fn($item) => $item !== '')
            ->map(function ($item) {
                // Convertir números
                if (is_numeric($item)) {
                    return str_contains($item, '.') ? (float) $item : (int) $item;
                }

                // Detectar fechas
                if (preg_match('/^\d{4}-\d{2}-\d{2}/', $item)) {
                    return $item;
                }

                // Detectar booleanos
                $lowerItem = strtolower($item);
                if ($lowerItem === 'true') return true;
                if ($lowerItem === 'false') return false;
                if ($lowerItem === 'null') return null;

                return $item;
            })
            ->toArray();
    }

    // ========== MÉTODOS DE CONSULTA PARA VISTAS ==========

    /**
     * Mapa de headers normalizado (memoizado por HeaderMap). Único punto de
     * entrada — ya no se recorre $this->headers a mano en este trait.
     */
    protected function headerMap(): HeaderMap
    {
        return HeaderMap::build($this->headers);
    }

    /**
     * Campos disponibles para filtros/orden (antes con exclusión distinta a
     * getAvailableHeaders(); ahora ambos usan la misma regla unificada de
     * HeaderMap, así ya no hay dos criterios de exclusión distintos conviviendo).
     */
    public function getAvailableFields(): array
    {
        return $this->headerMap()->availableFields();
    }

    /**
     * Headers completos ya filtrados (label, align, class...) listos para pintar
     */
    public function getAvailableHeaders(): array
    {
        return $this->headerMap()->availableHeaders();
    }

    /**
     * Operadores válidos para UN campo específico, según su tipo (idea 2)
     */
    public function getOperatorsForField(string $field): array
    {
        return $this->headerMap()->operatorsFor($field);
    }

    /**
     * Tipo declarado del campo (o el default del config si no se declaró).
     * Lo usa la vista para elegir el input correcto (fecha, número, texto...).
     */
    public function getFieldType(string $field): string
    {
        return $this->headerMap()->get($field)['type'] ?? config('datatable.default_type', 'string');
    }

    /**
     * Família del campo (string|numeric|date|phone|boolean). Determina el
     * `type` HTML del input de valor en el panel de filtros.
     */
    public function getFieldFamily(string $field): string
    {
        return $this->headerMap()->get($field)['family'] ?? 'string';
    }

    /**
     * Operadores del campo, agrupados por categoría visual (Comparación, Texto,
     * Rango, Lista, Nulos) — usar esta versión en la vista, filtrada por campo.
     */
    public function getGroupedOperators(string $field = ''): array
    {
        $operators = $field !== ''
            ? $this->getOperatorsForField($field)
            : $this->getAllOperators();

        $groupLabels = config('datatable.operator_groups', []);

        return collect($operators)
            ->groupBy(fn($op) => $groupLabels[$op] ?? 'Otros')
            ->map(fn($ops) => $ops->values()->toArray())
            ->toArray();
    }

    protected function getAllOperators(): array
    {
        return collect(config('datatable.families', []))
            ->pluck('operators')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Obtiene operadores disponibles (todos, sin filtrar por campo)
     */
    public function getAvailableOperators(): array
    {
        return $this->getAllOperators();
    }

    /**
     * Obtiene el número de filtros activos
     */
    public function getActiveFiltersCount(): int
    {
        return count(array_filter($this->filters, fn($f) => !empty($f['field'])));
    }

    // ========== MÉTODOS DE RESET ==========

    /**
     * Resetea la página cuando se actualiza la búsqueda
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Resetea la página cuando se actualizan los filtros. Si cambió el campo
     * de un filtro y el operador que tenía ya no es válido para el nuevo tipo,
     * se limpia para forzar a elegir uno correcto (evita mandar al backend un
     * operador inválido que Searchable descartaría en silencio de todos modos).
     */
    public function updatingFilters($value, $key): void
    {
        if (Str::endsWith($key, '.field')) {
            $index = (int) Str::before($key, '.field');

            if (isset($this->filters[$index])) {
                $validOperators = $this->getOperatorsForField($value);
                $currentOperator = $this->filters[$index]['operator'] ?? '';

                if ($currentOperator !== '' && !in_array($currentOperator, $validOperators)) {
                    $this->filters[$index]['operator'] = '';
                    $this->filters[$index]['value'] = '';
                }
            }
        }

        $this->resetPage();
    }

    /**
     * Resetea la página cuando se cambia items por página
     */
    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    // ========== MÉTODOS PROTEGIDOS ==========

    /**
     * Inicializa el primer filtro vacío
     */
    protected function initializeFilters(): void
    {
        if (empty($this->filters)) {
            $this->filters = [['field' => '', 'operator' => '', 'value' => '']];
        }
    }

    public function selectedAllCurrentPage(array $currentPageIds): void
    {
        if (
            count($this->selectedRows) === count($currentPageIds) &&
            empty(array_diff($currentPageIds, $this->selectedRows))
        ) {
            $this->selectedRows = [];
        } else {
            $this->selectedRows = $currentPageIds;
        }
    }
}
