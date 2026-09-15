<div>
    <div class="flex justify-between items-center mb-4">
        <flux:dropdown>

            <flux:button icon:trailing="chevron-down">Filtros</flux:button>

            <flux:menu>

                <flux:menu.submenu
                    heading="Ordenar por"
                    icon="bars-arrow-down"
                >
                    <flux:menu.radio.group>
                        <flux:menu.radio
                            wire:click="setSort('fecha_hora_inicio', 'desc')"
                            :checked="$this->isSortedBy('fecha_hora_inicio', 'desc')"
                        >
                            Más recientes primero
                        </flux:menu.radio>

                        <flux:menu.radio
                            wire:click="setSort('fecha_hora_inicio', 'asc')"
                            :checked="$this->isSortedBy('fecha_hora_inicio', 'asc')"
                        >
                            Más antiguos primero
                        </flux:menu.radio>

                        <flux:menu.radio
                            wire:click="setSort('nombres', 'asc')"
                            :checked="$this->isSortedBy('nombres', 'asc')"
                        >
                            Nombre (A-Z)
                        </flux:menu.radio>
                        <flux:menu.radio
                            wire:click="clearSort()"
                            :checked="$this->isSortedBy('id', 'asc')"
                        >
                            Restaurar
                        </flux:menu.radio>
                    </flux:menu.radio.group>
                </flux:menu.submenu>

                <flux:menu.submenu
                    heading="Por fecha"
                    icon="calendar"
                >
                    <flux:menu.radio.group>
                        <flux:menu.radio
                            wire:click="setFilter('fecha_hora_inicio', 'like', '{{ now()->subDay()->format('Y-m-d') }}')"
                        >
                            Ayer
                        </flux:menu.radio>
                        <flux:menu.radio
                            wire:click="setFilter('fecha_hora_inicio', 'like', '{{ now()->format('Y-m-d') }}')"
                        >
                            Hoy
                        </flux:menu.radio>
                        <flux:menu.radio
                            wire:click="setFilter('fecha_hora_inicio', 'like', '{{ now()->addDay()->format('Y-m-d') }}')"
                        >
                            Mañana
                        </flux:menu.radio>
                        <flux:menu.radio wire:click="removeFilter('fecha_hora_inicio')">
                            Todos
                        </flux:menu.radio>
                    </flux:menu.radio.group>
                </flux:menu.submenu>

                <flux:menu.submenu
                    heading="Por estado"
                    icon="flag"
                >
                    <flux:menu.radio.group>
                        <flux:menu.radio wire:click="setFilter('estado', '=', 'solicitada')">
                            Solicitada
                        </flux:menu.radio>
                        <flux:menu.radio wire:click="setFilter('estado', '=', 'agendada')">
                            Agendada
                        </flux:menu.radio>
                        <flux:menu.radio wire:click="setFilter('estado', '=', 'confirmada')">
                            Confirmada
                        </flux:menu.radio>
                        <flux:menu.radio wire:click="setFilter('estado', '=', 'en_atencion')">
                            En atencion
                        </flux:menu.radio>
                        <flux:menu.radio wire:click="setFilter('estado', '=', 'finalizada')">
                            Finalizada
                        </flux:menu.radio>
                        <flux:menu.radio wire:click="setFilter('estado', '=', 'cancelada')">
                            Cancelada
                        </flux:menu.radio>
                        <flux:menu.radio wire:click="setFilter('estado', '=', 'reprogramada')">
                            Reprogramada
                        </flux:menu.radio>
                        <flux:menu.radio wire:click="removeFilter('estado')">
                            Todos
                        </flux:menu.radio>
                    </flux:menu.radio.group>
                </flux:menu.submenu>


            </flux:menu>
        </flux:dropdown>

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
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-3  gap-4">
        @forelse ($this->rows() as $solicitud)
            <flux:card
                class="cursor-pointer hover:scale-105 hover:border-accent transition-transform duration-200 ease-in-out"
                wire:click="showSolicitud({{ $solicitud->id }})"
            >

                <div class="mb-2">
                    <flux:heading
                        size="xl"
                        class=" capitalize"
                    >
                        {{ $solicitud->tipo_cita }}
                    </flux:heading>
                    <flux:text>
                        <strong>Expediente # : </strong>
                        {{ $solicitud->expediente->id }}
                    </flux:text>
                </div>

                <div class="flex items-center gap-4">
                    <flux:avatar
                        size="lg"
                        name="{{ $solicitud->expediente->paciente->nombre_completo }}"
                        initials:single
                    />
                    <div>
                        <flux:heading size="lg">
                            {{ $solicitud->expediente->paciente->nombre_completo ?? '' }}
                        </flux:heading>
                        <div class="flex gap-1 items-center">
                            <flux:icon
                                name="phone"
                                class="size-4"
                            />
                            <flux:text>
                                {{ $solicitud->expediente->paciente->telefono ?? '' }}
                            </flux:text>
                        </div>
                        <div class="flex gap-1 items-center">
                            <flux:icon
                                name="envelope"
                                class="size-4"
                            />
                            <flux:text>
                                {{ $solicitud->expediente->paciente->email ?? '' }}
                            </flux:text>
                        </div>
                    </div>
                </div>
                <flux:separator class="my-4" />
                <div class="flex justify-between gap-4 items-center">
                    <div class="space-y-1">
                        <div class="flex items-center gap-1">
                            <flux:icon
                                name="calendar"
                                class="size-4"
                            />
                            <flux:text size="sm">
                                <strong> Fecha cita : </strong>
                                {{ $solicitud->fecha_hora_inicio->format('d F Y') }}
                            </flux:text>
                        </div>
                        <div class="flex items-center gap-1">
                            <flux:icon
                                name="clock"
                                class="size-4"
                            />
                            <flux:text size="sm">
                                <strong>Hora cita : </strong>
                                {{ $solicitud->fecha_hora_inicio->format('h:i a') }}
                            </flux:text>
                        </div>
                    </div>
                    <flux:badge
                        rounded
                        icon="flag"
                        size="sm"
                    >
                        {{ $solicitud->estado }}
                    </flux:badge>
                </div>
            </flux:card>
        @empty
            <div
                class="py-10 md:col-span-2 lg:col-span-3 text-center items-center gap-3 text-neutral-500 dark:text-neutral-400">
                <span>No se encontraron resultados.</span>
                <br />
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
    </div>

    <flux:modal
        name="solicitud-modal"
        flyout
        @close="resetData"
    >
        <form wire:submit.prevent="store">
            <flux:heading size="lg">Solicitud</flux:heading>
            <flux:separator class="my-4" />
            <div>
                <div class="grid grid-cols-2 gap-2">
                    <flux:input
                        label="Nombres"
                        wire:model="solicitud.expediente.paciente.nombres"
                        icon="pencil-square"
                    />
                    <flux:input
                        label="Apellidos"
                        wire:model="solicitud.expediente.paciente.apellidos"
                        icon="pencil-square"
                    />
                    <flux:input
                        label="Fecha nacimiento"
                        wire:model="solicitud.expediente.paciente.fecha_nacimiento"
                        type="date"
                        icon="cake"
                    />
                    <flux:select
                        label="Sexo"
                        wire:model="solicitud.expediente.paciente.sexo"
                    >
                        <flux:select.option value="">
                            -- Seleccion Genero --
                        </flux:select.option>
                        <flux:select.option value="M">
                            Masculino
                        </flux:select.option>
                        <flux:select.option value="F">
                            Femenino
                        </flux:select.option>
                        <flux:select.option value="otro">
                            Otro
                        </flux:select.option>
                    </flux:select>

                    <flux:select
                        label="Estado Civil"
                        wire:model="solicitud.expediente.paciente.estado_civil"
                    >
                        <flux:select.option value="">
                            -- Seleccion estado --
                        </flux:select.option>
                        <flux:select.option value="soltero">
                            Solter
                        </flux:select.option>
                        <flux:select.option value="casado">
                            Casado
                        </flux:select.option>
                        <flux:select.option value="divorciado">
                            Divorcido
                        </flux:select.option>
                        <flux:select.option value="viudo">
                            Viudo
                        </flux:select.option>
                        <flux:select.option value="union_libre">
                            Union libre
                        </flux:select.option>
                        <flux:select.option value="otro">
                            Otro
                        </flux:select.option>
                    </flux:select>

                    <flux:select
                        label="Religión"
                        wire:model="solicitud.expediente.paciente.religion"
                    >
                        <flux:select.option value="">
                            -- Seleccion religión --
                        </flux:select.option>
                        <flux:select.option value="catolico">
                            Católico
                        </flux:select.option>
                        <flux:select.option value="protestante">
                            Protestante
                        </flux:select.option>
                        <flux:select.option value="musulman">
                            Musulman
                        </flux:select.option>
                        <flux:select.option value="budista">
                            Budista
                        </flux:select.option>
                        <flux:select.option value="ortodoxo">
                            Ortodoxo
                        </flux:select.option>
                        <flux:select.option value="judio">
                            Judío
                        </flux:select.option>
                        <flux:select.option value="otro">
                            Otro
                        </flux:select.option>
                    </flux:select>

                    <flux:input
                        label="Dpi"
                        wire:model="solicitud.expediente.paciente.numero_dpi"
                        icon="identification"
                    />
                    <flux:input
                        label="Teléfono"
                        wire:model="solicitud.expediente.paciente.telefono"
                        icon="phone"
                    />
                    <flux:input
                        label="Teléfono Emergencia"
                        wire:model="solicitud.expediente.paciente.telefono_emergencia"
                        icon="device-phone-mobile"
                    />
                    <flux:input
                        label="Correo"
                        wire:model="solicitud.expediente.paciente.email"
                        icon="envelope"
                        type="email"
                    />
                    <flux:input
                        label="Dirección"
                        wire:model="solicitud.expediente.paciente.direccion"
                        icon="home"
                    />
                    <flux:input
                        label="Ocupación"
                        wire:model="solicitud.expediente.paciente.ocupacion"
                        icon="building-office"
                    />
                </div>
            </div>
        </form>
    </flux:modal>
</div>
