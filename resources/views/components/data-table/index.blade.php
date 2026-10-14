<div class="relative overflow-x-auto">
    <div class="p-4 flex items-center justify-between space-x-4">

        <div class="flex gap-2 items-center">
            <flux:select
                wire:model.live="per_page"
                wire:key="per-page-select"
            >
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
                    <flux:button
                        icon:trailing="chevron-down"
                        :disabled="count($this->selectedRows) < 1"
                    >
                        Acciones
                        @if (count($this->selectedRows) > 0)
                            <flux:badge
                                size="sm"
                                class="ml-2"
                                color="blue"
                            >
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

        <x-data-table.advance-filter />

    </div>

    {{-- ========== VISTA MOBILE ========== --}}
    <div class="lg:hidden">
        @forelse ($rows as $index => $rowData)
            <flux:card class="my-4">
                <flux:table>
                    @if ($selectable)
                        <flux:table.rows>
                            <flux:table.cell align="start">
                                <flux:checkbox
                                    wire:click="selectedAllCurrentPage({{ $rows->pluck('id')->toJson() }})"
                                    :checked="count($this->selectedRows) > 0 &&
                                        count($this->selectedRows) === $rows->count()"
                                />
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
                            <flux:table.cell
                                title="{{ data_get($rowData, $columnIndex, '') }}"
                                align="end"
                            >
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
                    <flux:button
                        wire:click="resetAllFilters"
                        variant="ghost"
                        size="sm"
                    >
                        Limpiar filtros
                    </flux:button>
                @endif
            </div>
        @endforelse

        <flux:pagination :paginator="$rows" />
    </div>

    <div
        wire:loading.delay
        wire:target="sort, search, updatingSearch, per_page, updatingPerPage, addFilter, deleteFilter, clearFilters, updatingFilters, gotoPage, nextPage, previousPage, selectedAllCurrentPage"
        class="p-8 w-full"
    >
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
    <div
        wire:loading.remove.delay
        wire:target="sort, search, updatingSearch, per_page, updatingPerPage, addFilter, deleteFilter, clearFilters, updatingFilters, gotoPage, nextPage, previousPage, selectedAllCurrentPage"
    >
        <div class="hidden lg:block">
            <flux:table :paginate="$rows">
                <flux:table.columns class="bg-white dark:bg-zinc-900">
                    @if ($selectable)
                        <flux:table.column
                            align="center"
                            width="50px"
                            title="Seleccionar todos"
                        >
                            <label class="flex items-center">
                                <flux:checkbox
                                    class="cursor-pointer"
                                    wire:click="selectedAllCurrentPage({{ $rows->pluck('id')->toJson() }})"
                                    :checked="count($this->selectedRows) > 0 &&
                                        count($this->selectedRows) === $rows->count()"
                                />
                            </label>
                        </flux:table.column>
                    @endif
                    @foreach ($headers as $header)
                        <flux:table.column
                            :sortable="$header['index'] != 'actions' && !isset($header['exclude'])"
                            :sorted="$this->sortBy === $header['index']"
                            :direction="$this->sortDirection"
                            wire:click="sort('{{ $header['index'] != 'actions' && !isset($header['exclude']) ? $header['index'] : '' }}')"
                            align="{{ $header['align'] ?? 'start' }}"
                            width="{{ $header['width'] ?? null }}"
                        >
                            <span class="{{ $header['class'] ?? '' }} cursor-pointer">
                                {{ $header['label'] }}
                            </span>
                        </flux:table.column>
                    @endforeach
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($rows as $index => $rowData)
                        @php $rowLoop = $loop; @endphp
                        <flux:table.row
                            wire:key="{{ $rowData->id }}"
                            class="dark:hover:bg-zinc-700 hover:bg-neutral-100 text-neutral-600 dark:text-neutral-200 {{ in_array($rowData->id, $this->selectedRows) ? 'dark:bg-zinc-700 bg-zinc-100' : '' }}"
                        >
                            @if ($selectable)
                                <flux:table.cell
                                    align="center"
                                    class="cursor-pointer"
                                >
                                    <label class="flex items-center px-2">
                                        <flux:checkbox
                                            wire:model.live="selectedRows"
                                            value="{{ $rowData->id }}"
                                        />
                                    </label>
                                </flux:table.cell>
                            @endif
                            @foreach ($headers as $header)
                                @php
                                    $columnIndex = $header['index'];
                                    $slotKey = 'column_' . str_replace('.', '_', $columnIndex);
                                    $slot = $capturedSlots[$slotKey] ?? null;
                                @endphp
                                <flux:table.cell
                                    title="{{ data_get($rowData, $columnIndex, '') }}"
                                    align="{{ $loop->last ? 'center' : $header['align'] ?? 'start' }}"
                                    width="{{ $header['width'] ?? null }}"
                                    class="{{ $header['class'] ?? null }}"
                                >
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
                            <flux:table.cell
                                align="center"
                                colspan="{{ count($headers) }}"
                            >
                                <div
                                    class="py-10 flex flex-col items-center gap-3 text-neutral-500 dark:text-neutral-400">
                                    <span>No se encontraron resultados.</span>
                                    @if ($this->getActiveFiltersCount() > 0 || $this->search !== '')
                                        <flux:button
                                            wire:click="resetAllFilters"
                                            variant="ghost"
                                            size="sm"
                                        >
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
