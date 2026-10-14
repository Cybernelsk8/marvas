@props([
    'label' => 'Filtros',
    'filters' => [],
])

<flux:dropdown>
    <flux:button
        icon:trailing="chevron-down"
        icon="squares-2x2"
    >
        {{ $label }}
    </flux:button>

    <flux:menu>
        @foreach ($filters as $filter)
            @php
                $type = $filter['type'] ?? 'filter';
                $options = $filter['options'] ?? [];
                $groupField =
                    collect($options)->first(
                        fn($option) => is_array($option['value'] ?? null) && isset($option['value']['field']),
                    )['value']['field'] ?? null;
            @endphp

            @if (in_array($type, ['sort', 'filter'], true))
                <flux:menu.submenu
                    :heading="$filter['label'] ?? ''"
                    :icon="$filter['icon'] ?? 'x-circle'"
                >
                    <flux:menu.radio.group>
                        @foreach ($options as $option)
                            @php
                                $optionValue = $option['value'] ?? null;
                                $encode = static fn($value) => json_encode(
                                    $value,
                                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
                                );

                                if ($type === 'sort') {
                                    $isChecked = is_array($optionValue)
                                        ? $this->isSortedBy(
                                            $optionValue['field'] ?? '',
                                            $optionValue['direction'] ?? null,
                                        )
                                        : $this->sortBy === 'id' && $this->sortDirection === 'desc';
                                } else {
                                    $optionField = is_array($optionValue) ? $optionValue['field'] ?? null : $groupField;
                                    $appliedFilter = collect($this->filters)->first(
                                        fn($activeFilter) => ($activeFilter['field'] ?? null) === $optionField,
                                    );

                                    $isChecked = is_array($optionValue)
                                        ? $appliedFilter !== null &&
                                            ($appliedFilter['operator'] ?? null) ===
                                                ($optionValue['operator'] ?? null) &&
                                            ($appliedFilter['value'] ?? null) === ($optionValue['value'] ?? null)
                                        : $appliedFilter === null;
                                }

                                $clickAction = match (true) {
                                    $type === 'sort' && is_array($optionValue) => 'setSort(' .
                                        $encode($optionValue['field']) .
                                        ', ' .
                                        $encode($optionValue['direction'] ?? 'asc') .
                                        ')',
                                    $type === 'sort' => 'clearSort()',
                                    is_array($optionValue) => 'setFilter(' .
                                        $encode($optionValue['field']) .
                                        ', ' .
                                        $encode($optionValue['operator']) .
                                        ', ' .
                                        $encode($optionValue['value']) .
                                        ')',
                                    default => 'removeFilter(' . $encode($groupField ?? '') . ')',
                                };
                            @endphp

                            <flux:menu.radio
                                wire:click="{{ $clickAction }}"
                                :checked="$isChecked"
                            >
                                {{ $option['label'] ?? '' }}
                            </flux:menu.radio>
                        @endforeach
                    </flux:menu.radio.group>
                </flux:menu.submenu>
            @endif
        @endforeach
    </flux:menu>
</flux:dropdown>
