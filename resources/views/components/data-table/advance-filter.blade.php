<div class="flex gap-1">
    <flux:input
        wire:model.live.debounce.500ms="search"
        icon="magnifying-glass"
        placeholder="Buscar ..."
        type="search"
        wire:key="search-input"
    />

    <flux:dropdown>
        <flux:tooltip content="Filtros avanzados">
            <flux:button
                icon="funnel"
                icon-variant="outline"
                iconTrailing="chevron-down"
            >
                @if ($this->getActiveFiltersCount() > 0)
                    <flux:badge
                        size="sm"
                        color="blue"
                    >
                        {{ $this->getActiveFiltersCount() }}
                    </flux:badge>
                @endif
            </flux:button>
        </flux:tooltip>
        <flux:menu keep-open>
            <div class="flex justify-center gap-4">
                <flux:button
                    wire:click="addFilter()"
                    variant="primary"
                    icon="plus"
                    size="sm"
                    title="Agregar filtro"
                />

                <flux:button
                    wire:click="clearFilters()"
                    variant="danger"
                    icon="trash"
                    size="sm"
                    title="Limpiar filtros"
                />

            </div>

            <flux:menu.separator />

            @foreach ($this->filters as $index => $filter)
                <flux:menu.item>
                    <div class="flex gap-2 items-center">

                        <flux:select
                            wire:key="field-campo-{{ $index }}"
                            wire:model.live.debounce.500ms="filters.{{ $index }}.field"
                            placeholder="Campo"
                            size="sm"
                        >
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
                            placeholder="Operador"
                            size="sm"
                        >
                            @foreach ($this->getGroupedOperators($filter['field'] ?? '') as $group => $operators)
                                <optgroup label="{{ $group }}">
                                    @foreach ($operators as $operator)
                                        <flux:select.option value="{{ $operator }}">
                                            {{ $operator }}
                                        </flux:select.option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </flux:select>

                        @php
                            $currentField = $filter['field'] ?? '';
                            $currentOperator = strtolower($filter['operator'] ?? '');
                            $family = $currentField ? $this->getFieldFamily($currentField) : 'string';
                            $fieldType = $currentField ? $this->getFieldType($currentField) : 'string';
                            $isRangeOrList = in_array($currentOperator, ['between', 'not between', 'in', 'not in']);
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

                        <flux:input
                            x-on:keydown.stop=""
                            wire:key="field-value-{{ $index }}"
                            type="{{ $inputType }}"
                            :disabled="$isNullOperator"
                            wire:model.live.debounce.500ms="filters.{{ $index }}.value"
                            placeholder="{{ $placeholder }}"
                            size="sm"
                        />

                        <flux:icon.x-circle
                            wire:click="deleteFilter({{ $index }})"
                            class="cursor-pointer text-red-500"
                        />
                    </div>
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>


</div>
