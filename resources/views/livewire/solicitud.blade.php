<div>

    <div class="flex justify-center mb-4">
        <flux:modal.trigger name="nueva-cita-modal">
            <flux:button
                icon="calendar-days"
                title="Agendar cita"
                variant="primary"
            >
                Agendar cita
            </flux:button>
        </flux:modal.trigger>
    </div>

    {{-- FILTROS --}}
    <div class="flex justify-between items-center mb-4">
        <x-data-table.filter
            :filters="$this->filtersOptions"
            label="Filtros"
        />
        <x-data-table.advance-filter />

    </div>

    {{-- CARDS --}}
    <div class="grid md:grid-cols-2 lg:grid-cols-3 mb-6 gap-4">
        @forelse ($this->rows() as $solicitud)
            <flux:card class="hover:scale-105 hover:border-accent transition-transform duration-200 ease-in-out">
                <flux:card.header>
                    <flux:card.heading
                        size="xl"
                        class="capitalize"
                    >
                        {{ $solicitud->tipo_cita }}
                    </flux:card.heading>
                    <flux:card.subheading class="flex items-center justify-between">

                        <flux:text>
                            <strong>Expediente # : </strong>
                            {{ $solicitud->expediente->id }}
                        </flux:text>
                        <flux:text size="sm">
                            {{ $solicitud->created_at->format('d F Y') }}
                        </flux:text>

                    </flux:card.subheading>
                    <flux:card.actions>
                        <flux:dropdown
                            position="bottom"
                            align="end"
                        >
                            <flux:button
                                icon:trailing="ellipsis-vertical"
                                variant="ghost"
                            />

                            <flux:navmenu>
                                <flux:navmenu.item icon="document-text">
                                    Generar link de formulario
                                </flux:navmenu.item>
                                <flux:navmenu.item
                                    icon="pencil-square"
                                    wire:click="showSolicitud({{ $solicitud->id }})"
                                >
                                    Editar datos paciente
                                </flux:navmenu.item>
                            </flux:navmenu>
                        </flux:dropdown>
                    </flux:card.actions>
                </flux:card.header>

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

                <flux:card.footer>
                    <flux:card.actions>
                        <flux:button variant="ghost">Cambiar fecha</flux:button>
                        <flux:button variant="primary">Confirmar cita</flux:button>
                    </flux:card.actions>
                </flux:card.footer>

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

    <flux:pagination :paginator="$this->rows" />

    {{-- MODALES --}}
    <flux:modal
        name="nueva-cita-modal"
        flyout
        @close="resetData"
    >
        <x-calendar
            wire:key="cita-fecha"
            label="Selecciona la fecha de tu cita *"
            wire:model.live="fecha"
            :min-date="now()->format('Y-m-d')"
            :max-date="now()->addMonths(3)->format('Y-m-d')"
            size="md"
        />

    </flux:modal>

    <flux:modal
        name="solicitud-modal"
        flyout
        @close="resetData"
    >
        <form wire:submit.prevent="store">
            <flux:heading size="lg">Solicitud</flux:heading>
            <flux:separator class="my-4" />
            <div class="grid grid-cols-1 gap-4">
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
                            Soltero
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
            <div class="flex mt-4">
                <flux:spacer />
                <flux:button
                    type="submit"
                    variant="primary"
                    icon="check"
                >
                    Guardar cambios
                </flux:button>
            </div>
        </form>
    </flux:modal>


</div>
