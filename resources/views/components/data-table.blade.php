<div class="relative overflow-x-auto">
    <div class="p-4 flex items-center justify-between space-x-4">

        <div class="flex gap-2 items-center">
            <flux:select wire:model.live="per_page" wire:key="per-page-select">
                <flux:select.option>5</flux:select.option>
                <flux:select.option>10</flux:select.option>
                <flux:select.option>20</flux:select.option>
                <flux:select.option>50</flux:select.option>
                <flux:select.option>100</flux:select.option>
                <flux:select.option>300</flux:select.option>
                <flux:select.option>500</flux:select.option>
                <flux:select.option>1000</flux:select.option>
            </flux:select>

            @if ($massActions)
                <flux:dropdown>
                    <flux:button icon:trailing="chevron-down" :disabled="count($this->selectedRows) < 1">
                        Acciones
                        @if (count($this->selectedRows) > 0)
                            <flux:badge size="sm" class="ml-2" color="blue">
                                {{ count($this->selectedRows) }}
                            </flux:badge>
                        @endif
                    </flux:button>
                    <flux:menu>
                        {{ $massActions }}
                    </flux:menu>
                </flux:dropdown>
            @endif

        </div>

        <div class="flex gap-1">
            <flux:input wire:model.live.debounce.500ms="search" icon="magnifying-glass" placeholder="Buscar ..."
                type="search" wire:key="search-input" />

            @if ($advanceFilter)

                <flux:dropdown>
                    <flux:button icon="funnel" icon-variant="outline" iconTrailing="chevron-down">
                        @if ($this->getActiveFiltersCount() > 0)
                            <flux:badge size="sm" color="blue">
                                {{ $this->getActiveFiltersCount() }}
                            </flux:badge>
                        @endif
                    </flux:button>
                    <flux:menu keep-open>
                        <div class="flex justify-center gap-4">
                            <flux:button wire:click="addFilter()" variant="primary" icon="plus" size="sm"
                                title="Agregar filtro">
                            </flux:button>
                            <flux:button wire:click="clearFilters()" variant="danger" icon="trash" size="sm"
                                title="Limpiar filtros">
                            </flux:button>
                        </div>

                        <flux:menu.separator />

                        @foreach ($this->filters as $index => $filter)
                            <flux:menu.item>
                                <div class="flex gap-2 items-center">

                                    <flux:select wire:key="field-campo-{{ $index }}"
                                        wire:model.live.debounce.500ms="filters.{{ $index }}.field"
                                        placeholder="Campo" size="sm">
                                        @foreach ($this->getAvailableHeaders() as $header)
                                            <flux:select.option value="{{ $header['index'] }}">
                                                {{ $header['label'] }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    {{-- El wire:key incluye el campo actual: fuerza a Livewire a recrear
                                         el <select> cuando cambia el campo, evitando que quede seleccionada
                                         una <option> vieja que ya no existe en la nueva lista filtrada. --}}
                                    <flux:select
                                        wire:key="field-operator-{{ $index }}-{{ $filter['field'] ?? '' }}"
                                        wire:model.live.debounce.500ms="filters.{{ $index }}.operator"
                                        placeholder="Operador" size="sm">
                                        @foreach ($this->getGroupedOperators($filter['field'] ?? '') as $group => $operators)
                                            <optgroup label="{{ $group }}">
                                                @foreach ($operators as $operator)
                                                    <flux:select.option value="{{ $operator }}">
                                                        {{ $operator }}</flux:select.option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </flux:select>

                                    @php
                                        $currentField = $filter['field'] ?? '';
                                        $currentOperator = strtolower($filter['operator'] ?? '');
                                        $family = $currentField ? $this->getFieldFamily($currentField) : 'string';
                                        $fieldType = $currentField ? $this->getFieldType($currentField) : 'string';
                                        $isRangeOrList = in_array($currentOperator, [
                                            'between',
                                            'not between',
                                            'in',
                                            'not in',
                                        ]);
                                        $isNullOperator = in_array($currentOperator, ['null', 'not null']);

                                        // Tipo de <input> HTML: para operadores de rango/lista se deja texto
                                        // (siguen siendo valores separados por coma), para un valor único se
                                        // usa el input nativo del navegador según la família del campo.
                                        $inputType = match (true) {
                                            $isRangeOrList => 'text',
                                            $fieldType === 'datetime' => 'datetime-local',
                                            $fieldType === 'time' => 'time',
                                            $family === 'date' => 'date',
                                            $family === 'numeric' => 'number',
                                            default => 'text',
                                        };

                                        $placeholder = match (true) {
                                            $isNullOperator => 'No requiere valor',
                                            in_array($currentOperator, ['between', 'not between']) => 'Desde, Hasta',
                                            in_array($currentOperator, ['in', 'not in']) => 'Valor1, Valor2, ...',
                                            default => 'Valores',
                                        };
                                    @endphp

                                    <flux:input x-on:keydown.stop="" wire:key="field-value-{{ $index }}"
                                        type="{{ $inputType }}" :disabled="$isNullOperator"
                                        wire:model.live.debounce.500ms="filters.{{ $index }}.value"
                                        placeholder="{{ $placeholder }}" size="sm" />

                                    <flux:icon.x-circle wire:click="deleteFilter({{ $index }})"
                                        class="cursor-pointer text-red-500" />
                                </div>
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>

            @endif
        </div>

    </div>

    {{-- ========== VISTA MOBILE ========== --}}
    <div class="lg:hidden">
        @forelse ($rows as $index => $rowData)
            <flux:card class="my-4">
                <flux:table>
                    @if ($selectable)
                        <flux:table.rows>
                            <flux:table.cell align="start">
                                <flux:checkbox wire:click="selectedAllCurrentPage({{ $rows->pluck('id')->toJson() }})"
                                    :checked="count($this->selectedRows) > 0 && count($this->selectedRows) === $rows->count()" />
                            </flux:table.cell>
                        </flux:table.rows>
                    @endif
                    @foreach ($headers as $header)
                        <flux:table.rows class="even:bg-zinc-100 dark:even:bg-zinc-600">
                            @php
                                $columnIndex = $header['index'];
                                $slotKey = 'column_' . str_replace('.', '_', $columnIndex);
                                $slot = $capturedSlots[$slotKey] ?? null;
                            @endphp
                            <flux:table.cell align="start">
                                <span class="font-medium uppercase pl-4">
                                    {{ $header['label'] . ($header['index'] !== 'actions' ? ' :' : '') }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell title="{{ data_get($rowData, $columnIndex, '') }}" align="end">
                                <div class="pr-4">
                                    @if ($slot)
                                        @if (app()->isLocal())
                                            @php
                                                $result = $slot->call($this, $rowData, $loop ?? null);
                                                echo $result instanceof HtmlString ? $result->toHtml() : $result;
                                            @endphp
                                        @else
                                            @php
                                                try {
                                                    $result = $slot->call($this, $rowData, $loop ?? null);
                                                    echo $result instanceof HtmlString ? $result->toHtml() : $result;
                                                } catch (\Throwable $e) {
                                                    // En producción la celda queda vacía sin romper la tabla
                                                }
                                            @endphp
                                        @endif
                                    @else
                                        {{ data_get($rowData, $columnIndex, '') }}
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.rows>
                    @endforeach
                </flux:table>
            </flux:card>
        @empty
            <div class="py-10 flex flex-col items-center gap-3 text-neutral-500 dark:text-neutral-400">
                <span>No se encontraron resultados.</span>
                @if ($this->getActiveFiltersCount() > 0 || $this->search !== '')
                    <flux:button wire:click="resetAllFilters" variant="ghost" size="sm">
                        Limpiar filtros
                    </flux:button>
                @endif
            </div>
        @endforelse

        <flux:pagination :paginator="$rows" />
    </div>

    <div wire:loading.delay
        wire:target="sort, search, updatingSearch, per_page, updatingPerPage, addFilter, deleteFilter, clearFilters, updatingFilters, gotoPage, nextPage, previousPage, selectedAllCurrentPage"
        class="p-8 w-full">
        <flux:skeleton.group animate="shimmer">
            <flux:table>
                <flux:table.columns>
                    @foreach ($headers as $header)
                        <flux:table.column>{{ $header['label'] }}</flux:table.column>
                    @endforeach
                </flux:table.columns>

                <flux:table.rows>
                    @foreach (range(1, $this->per_page) as $order)
                        <flux:table.row>
                            @foreach ($headers as $header)
                                <flux:table.cell>
                                    <flux:skeleton.line />
                                </flux:table.cell>
                            @endforeach
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:skeleton.group>
    </div>

    {{-- ========== VISTA DESKTOP ========== --}}
    <div wire:loading.remove.delay
        wire:target="sort, search, updatingSearch, per_page, updatingPerPage, addFilter, deleteFilter, clearFilters, updatingFilters, gotoPage, nextPage, previousPage, selectedAllCurrentPage">
        <div class="hidden lg:block">
            <flux:table :paginate="$rows">
                <flux:table.columns class="bg-white dark:bg-zinc-900">
                    @if ($selectable)
                        <flux:table.column align="center" width="50px" title="Seleccionar todos">
                            <label class="flex items-center">
                                <flux:checkbox class="cursor-pointer"
                                    wire:click="selectedAllCurrentPage({{ $rows->pluck('id')->toJson() }})"
                                    :checked="count($this->selectedRows) > 0 && count($this->selectedRows) === $rows->count()" />
                            </label>
                        </flux:table.column>
                    @endif
                    @foreach ($headers as $header)
                        <flux:table.column :sortable="(($header['index'] != 'actions') && !(isset($header['exclude'])))"
                            :sorted="$this->sortBy === $header['index']" :direction="$this->sortDirection"
                            wire:click="sort('{{ $header['index'] != 'actions' && !isset($header['exclude']) ? $header['index'] : '' }}')"
                            align="{{ $header['align'] ?? 'start' }}" width="{{ $header['width'] ?? null }}">
                            <span class="{{ $header['class'] ?? '' }} cursor-pointer">
                                {{ $header['label'] }}
                            </span>
                        </flux:table.column>
                    @endforeach
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($rows as $index => $rowData)
                        @php $rowLoop = $loop; @endphp
                        <flux:table.row wire:key="{{ $rowData->id }}"
                            class="dark:hover:bg-neutral-700 hover:bg-neutral-100 text-neutral-600 dark:text-neutral-200 {{ in_array($rowData->id, $this->selectedRows) ? 'dark:bg-zinc-700 bg-zinc-100' : '' }}">
                            @if ($selectable)
                                <flux:table.cell align="center" class="cursor-pointer">
                                    <label class="flex items-center px-2">
                                        <flux:checkbox wire:model.live="selectedRows" value="{{ $rowData->id }}" />
                                    </label>
                                </flux:table.cell>
                            @endif
                            @foreach ($headers as $header)
                                @php
                                    $columnIndex = $header['index'];
                                    $slotKey = 'column_' . str_replace('.', '_', $columnIndex);
                                    $slot = $capturedSlots[$slotKey] ?? null;
                                @endphp
                                <flux:table.cell title="{{ data_get($rowData, $columnIndex, '') }}"
                                    align="{{ $loop->last ? 'center' : $header['align'] ?? 'start' }}"
                                    width="{{ $header['width'] ?? null }}" class="{{ $header['class'] ?? null }}">
                                    <div class="px-2">
                                        @if ($slot)
                                            @if (app()->isLocal())
                                                @php
                                                    $result = $slot->call($this, $rowData, $rowLoop ?? null);
                                                    echo $result instanceof HtmlString ? $result->toHtml() : $result;
                                                @endphp
                                            @else
                                                @php
                                                    try {
                                                        $result = $slot->call($this, $rowData, $rowLoop ?? null);
                                                        echo $result instanceof HtmlString
                                                            ? $result->toHtml()
                                                            : $result;
                                                    } catch (\Throwable $e) {
                                                        // En producción la celda queda vacía sin romper la tabla
                                                    }
                                                @endphp
                                            @endif
                                        @else
                                            @if (isset($header['type']))
                                                {{ maskFormatVal(data_get($rowData, $columnIndex, ''), $header['type']) }}
                                            @else
                                                {{ data_get($rowData, $columnIndex, '') }}
                                            @endif
                                        @endif
                                    </div>
                                </flux:table.cell>
                            @endforeach
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell align="center" colspan="{{ count($headers) }}">
                                <div
                                    class="py-10 flex flex-col items-center gap-3 text-neutral-500 dark:text-neutral-400">
                                    <span>No se encontraron resultados.</span>
                                    @if ($this->getActiveFiltersCount() > 0 || $this->search !== '')
                                        <flux:button wire:click="resetAllFilters" variant="ghost" size="sm">
                                            Limpiar filtros
                                        </flux:button>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

</div>
