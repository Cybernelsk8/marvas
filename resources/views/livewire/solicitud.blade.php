<div>

    <div class="flex justify-center mb-4">
        <flux:modal.trigger name="nueva-cita-modal">
            <flux:button icon="calendar-days" title="Agendar cita" variant="primary">
                Agendar cita
            </flux:button>
        </flux:modal.trigger>
    </div>

    {{-- FILTROS --}}
    <div class="flex justify-between items-center mb-4">
        <x-data-table.filter :filters="$this->filtersOptions" label="Filtros" />
        <x-data-table.advance-filter />

    </div>

    {{-- CARDS --}}
    <div class="grid md:grid-cols-2 lg:grid-cols-3 mb-6 gap-4">
        @forelse ($this->rows() as $solicitud)
            <flux:card class="hover:scale-105 hover:border-accent transition-transform duration-200 ease-in-out">
                <flux:card.header>
                    <flux:card.heading size="xl" class="capitalize">
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
                        <flux:dropdown position="bottom" align="end">
                            <flux:button icon:trailing="ellipsis-vertical" variant="ghost" />

                            <flux:navmenu>
                                @can('requests.edit')
                                    <flux:navmenu.item icon="document-text"
                                        wire:click="generarLinkFormulario({{ $solicitud->id }})">
                                        Generar link de formulario
                                    </flux:navmenu.item>
                                @endcan
                                <flux:navmenu.item icon="pencil-square"
                                    wire:click="showSolicitud({{ $solicitud->id }})">
                                    Editar datos paciente
                                </flux:navmenu.item>
                            </flux:navmenu>
                        </flux:dropdown>
                    </flux:card.actions>
                </flux:card.header>

                <div class="flex items-center gap-4">
                    <flux:avatar size="lg" name="{{ $solicitud->expediente->paciente->nombre_completo }}"
                        initials:single />
                    <div>
                        <flux:heading size="lg">
                            {{ $solicitud->expediente->paciente->nombre_completo ?? '' }}
                        </flux:heading>
                        <div class="flex gap-1 items-center">
                            <flux:icon name="phone" class="size-4" />
                            <a class="underline underline-offset-2"
                                href="tel:{{ preg_replace('/[^0-9+]/', '', $solicitud->expediente->paciente->telefono ?? '') }}">
                                {{ $solicitud->expediente->paciente->telefono ?? '' }}
                            </a>
                        </div>
                        <div class="flex gap-1 items-center">
                            <flux:icon name="envelope" class="size-4" />
                            <flux:text>
                                {{ $solicitud->expediente->paciente->email ?? '' }}
                            </flux:text>
                        </div>
                        @if ($solicitud->notas_recepcion)
                            <flux:text class="mt-2">
                                <strong>Motivo:</strong> {{ $solicitud->notas_recepcion }}
                            </flux:text>
                        @endif
                    </div>
                </div>

                <flux:separator class="my-4" />

                <div class="flex justify-between gap-4 items-center">
                    <div class="space-y-1">

                        <div class="flex items-center gap-1">
                            <flux:icon name="calendar" class="size-4" />
                            <flux:text size="sm">
                                <strong> Fecha cita : </strong>
                                {{ $solicitud->fecha_hora_inicio->format('d F Y') }}
                            </flux:text>
                        </div>
                        <div class="flex items-center gap-1">
                            <flux:icon name="clock" class="size-4" />
                            <flux:text size="sm">
                                <strong>Hora cita : </strong>
                                {{ $solicitud->fecha_hora_inicio->format('h:i a') }}
                            </flux:text>
                        </div>
                        <div class="flex items-center gap-1">
                            <flux:icon name="map-pin" class="size-4" />
                            <flux:text size="sm">
                                <strong>Sede:</strong>
                                {{ $solicitud->sucursal?->nombre ?? 'Pendiente de asignar' }}
                            </flux:text>
                        </div>
                    </div>
                    <flux:badge rounded icon="flag" size="sm">
                        {{ $solicitud->estado }}
                    </flux:badge>
                </div>

                <flux:card.footer>
                    <flux:card.actions>
                        @if ($solicitud->estado === 'solicitada')
                            @can('requests.reschedule')
                                <flux:button variant="ghost" wire:click="prepararReprogramacion({{ $solicitud->id }})">
                                    Reprogramar
                                </flux:button>
                            @endcan
                            @can('requests.confirm')
                                <flux:button variant="primary" wire:click="prepararConfirmacion({{ $solicitud->id }})">
                                    Confirmar
                                </flux:button>
                            @endcan
                            @can('requests.cancel')
                                <flux:button variant="danger" wire:click="prepararCancelacion({{ $solicitud->id }})">
                                    Cancelar
                                </flux:button>
                            @endcan
                        @endif
                    </flux:card.actions>
                </flux:card.footer>

            </flux:card>
        @empty
            <div
                class="py-10 md:col-span-2 lg:col-span-3 text-center items-center gap-3 text-neutral-500 dark:text-neutral-400">
                <span>No se encontraron resultados.</span>
                <br />
                @if ($this->getActiveFiltersCount() > 0 || $this->search !== '')
                    <flux:button wire:click="resetAllFilters" variant="ghost" size="sm">
                        Limpiar filtros
                    </flux:button>
                @endif
            </div>
        @endforelse
    </div>

    <flux:pagination :paginator="$this->rows" />

    {{-- MODALES --}}
    <flux:modal name="confirmar-cita-modal" class="max-w-lg">
        <form wire:submit="confirmar({{ $solicitudId }})" class="space-y-4">
            <flux:heading size="lg">Confirmar cita</flux:heading>
            <flux:text>
                Asigna la sede, profesional y especialidad de atención, y confirma con el paciente la fecha y hora. La cita cambiará al estado
                <strong>confirmada</strong>.
            </flux:text>
            @if ($sucursalesActivas->isEmpty())
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.heading>No hay sedes activas</flux:callout.heading>
                    <flux:callout.text>
                        Activa o registra una sede antes de confirmar citas.
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:select label="Sede de atención *" wire:model.live="sucursalConfirmacion"
                    placeholder="Selecciona la sede" required>
                    @foreach ($sucursalesActivas as $sucursal)
                        <flux:select.option value="{{ $sucursal->id }}">
                            {{ $sucursal->nombre }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @error('sucursalConfirmacion')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
                @if ($sucursalConfirmacion)
                    @if ($profesionalesConfirmacion->isEmpty())
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            <flux:callout.heading>{{ $mbiEvaluatorRequired ? 'Sin Evaluador MBI configurado' : 'Sin profesionales disponibles' }}</flux:callout.heading>
                            <flux:callout.text>
                                {{ $mbiEvaluatorRequired
                                    ? 'Configura un Evaluador MBI activo para esta sede en Administración de disponibilidad.'
                                    : 'Asocia profesionales activos a la sede seleccionada antes de confirmar.' }}
                            </flux:callout.text>
                        </flux:callout>
                    @else
                        <flux:select label="{{ $mbiEvaluatorRequired ? 'Profesional de atención (Evaluador MBI) *' : 'Profesional de atención *' }}" wire:model.live="profesionalConfirmacion"
                            placeholder="Selecciona el profesional" required>
                            @foreach ($profesionalesConfirmacion as $profesional)
                                <flux:select.option value="{{ $profesional->id }}">
                                    {{ $mbiEvaluatorRequired ? 'Evaluador MBI — ' : '' }}{{ $profesional->nombre_completo }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('profesionalConfirmacion')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    @endif
                @endif
                @if ($profesionalConfirmacion)
                    @if ($especialidadesConfirmacion->isEmpty())
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            <flux:callout.heading>Sin especialidades disponibles</flux:callout.heading>
                            <flux:callout.text>Asocia una especialidad activa al profesional antes de confirmar.</flux:callout.text>
                        </flux:callout>
                    @else
                        <flux:select label="Especialidad de atención *" wire:model.live="especialidadConfirmacion"
                            placeholder="Selecciona la especialidad" required>
                            @foreach ($especialidadesConfirmacion as $especialidad)
                                <flux:select.option value="{{ $especialidad->id }}">{{ $especialidad->nombre }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('especialidadConfirmacion')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    @endif
                @endif
            @endif
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Volver</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="confirmar"
                    :disabled="$sucursalesActivas->isEmpty() || ! $sucursalConfirmacion || ! $profesionalConfirmacion || ! $especialidadConfirmacion">
                    Confirmar cita
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="formulario-paciente-link-modal" class="max-w-xl">
        <div class="space-y-4">
            <flux:heading size="lg">Formulario para {{ $formularioNombre }}</flux:heading>
            <flux:text>
                Comparte este enlace para que el paciente complete sus datos. El enlace vence en 7 días.
            </flux:text>
            <flux:input icon="key" label="Enlace del formulario" :value="$formularioUrl" readonly copyable />
            @if ($formularioUrl)
                <div class="flex justify-end gap-2">
                    <flux:button icon="clipboard"
                        x-on:click="navigator.clipboard.writeText(@js($formularioUrl))">
                        Copiar enlace
                    </flux:button>
                    <flux:button
                        href="{{ 'https://wa.me/?text=' . urlencode('Hola ' . $formularioNombre . ', por favor completa tus datos en este formulario: ' . $formularioUrl) }}"
                        target="_blank" rel="noopener noreferrer" icon="chat-bubble-left-right">
                        Compartir por WhatsApp
                    </flux:button>
                </div>
            @endif
        </div>
    </flux:modal>

    <flux:modal name="nueva-cita-modal" flyout @close="resetData">
        <x-calendar wire:key="cita-fecha" label="Selecciona la fecha de tu cita *" wire:model.live="fecha"
            :min-date="now()->format('Y-m-d')" :max-date="now()->addMonths(3)->format('Y-m-d')" size="md" />

    </flux:modal>

    <flux:modal name="solicitud-modal" flyout @close="resetData">
        <form wire:submit.prevent="store">
            <flux:heading size="lg">Solicitud</flux:heading>
            <flux:separator class="my-4" />
            @if (!empty($solicitud['notas_recepcion']))
                <flux:callout icon="chat-bubble-left-right" class="mb-4">
                    <flux:callout.heading>Motivo indicado por el paciente</flux:callout.heading>
                    <flux:callout.text>{{ $solicitud['notas_recepcion'] }}</flux:callout.text>
                </flux:callout>
            @endif
            <div class="grid grid-cols-1 gap-4">
                <div class="grid grid-cols-2 gap-2">
                    <flux:input label="Nombres" wire:model.live.debounce.400ms="solicitud.expediente.paciente.nombres"
                        icon="pencil-square" :disabled="!auth()->user()->can('requests.edit')" />
                    <flux:input label="Apellidos" wire:model.live.debounce.400ms="solicitud.expediente.paciente.apellidos"
                        icon="pencil-square" :disabled="!auth()->user()->can('requests.edit')" />
                    <flux:input label="Fecha nacimiento" wire:model="solicitud.expediente.paciente.fecha_nacimiento"
                        type="date" icon="cake" :disabled="!auth()->user()->can('requests.edit')" />
                    <flux:select label="Sexo" wire:model="solicitud.expediente.paciente.sexo"
                        :disabled="!auth()->user()->can('requests.edit')">
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

                    <flux:select label="Estado Civil" wire:model="solicitud.expediente.paciente.estado_civil"
                        :disabled="!auth()->user()->can('requests.edit')">
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

                    <flux:select label="Religión" wire:model="solicitud.expediente.paciente.religion"
                        :disabled="!auth()->user()->can('requests.edit')">
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

                    <flux:input label="Dpi" wire:model.live.debounce.400ms="solicitud.expediente.paciente.numero_dpi"
                        icon="identification" :disabled="!auth()->user()->can('requests.edit')" />
                    <flux:input label="Teléfono" wire:model.live.debounce.400ms="solicitud.expediente.paciente.telefono" icon="phone"
                        :disabled="!auth()->user()->can('requests.edit')" />
                    <flux:input label="Teléfono Emergencia"
                        wire:model="solicitud.expediente.paciente.telefono_emergencia" icon="device-phone-mobile"
                        :disabled="!auth()->user()->can('requests.edit')" />
                    <flux:input label="Correo" wire:model.live.debounce.400ms="solicitud.expediente.paciente.email" icon="envelope"
                        type="email" :disabled="!auth()->user()->can('requests.edit')" />
                    <flux:input label="Dirección" wire:model="solicitud.expediente.paciente.direccion" icon="home"
                        :disabled="!auth()->user()->can('requests.edit')" />
                    <flux:input label="Ocupación" wire:model="solicitud.expediente.paciente.ocupacion"
                        icon="building-office" :disabled="!auth()->user()->can('requests.edit')" />
                </div>
            </div>
            @can('requests.edit')
                @if ($this->possiblePatientMatches->isNotEmpty())
                    <flux:callout variant="warning" icon="exclamation-triangle" class="mt-4">
                    <flux:callout.heading>Posibles pacientes existentes</flux:callout.heading>
                    <flux:callout.text>
                        Selecciona un expediente solo si confirmaste que se trata de la misma persona. Se trasladará el historial de esta solicitud y el expediente de origen quedará inactivo y enlazado. Los datos distintos no reemplazarán los del paciente existente; se conservarán para revisión.
                    </flux:callout.text>
                    <div class="mt-3 space-y-2">
                        @foreach ($this->possiblePatientMatches as $match)
                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-300/40 p-3">
                                <div>
                                    <div class="font-medium">{{ $match->nombre_completo }}</div>
                                    <div class="text-sm">{{ $match->numero_dpi ?: 'Sin DPI' }} · {{ $match->telefono ?: 'Sin teléfono' }} · {{ $match->email ?: 'Sin correo' }}</div>
                                </div>
                                @can('requests.edit')
                                    <flux:button
                                        type="button"
                                        size="sm"
                                        variant="{{ $selectedExistingPatientId === $match->id ? 'primary' : 'ghost' }}"
                                        wire:click="selectExistingPatient({{ $match->id }})"
                                    >
                                        {{ $selectedExistingPatientId === $match->id ? 'Seleccionado' : 'Seleccionar expediente' }}
                                    </flux:button>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                    </flux:callout>

                    @if ($selectedExistingPatientId)
                        <div class="mt-3 space-y-3 rounded-lg border p-4">
                            <flux:checkbox
                                wire:model="confirmPatientReuse"
                                label="Confirmo que esta solicitud pertenece a la misma persona y deseo trasladar su historial al expediente seleccionado."
                            />
                            @error('confirmPatientReuse')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                            @error('selectedExistingPatientId')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                            <div class="flex justify-end">
                                <flux:button
                                    type="button"
                                    variant="primary"
                                    icon="arrow-right"
                                    wire:click="reuseExistingPatient"
                                >
                                    Vincular solicitud y trasladar historial
                                </flux:button>
                            </div>
                        </div>
                    @endif
                @endif
            @endcan
            @can('requests.edit')
                <div class="flex mt-4">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary" icon="check">
                        Guardar cambios
                    </flux:button>
                </div>
            @endcan
        </form>
    </flux:modal>

    <flux:modal name="reprogramar-cita-modal" flyout @close="resetData">
        <form wire:submit="reprogramar" class="space-y-4">
            <flux:heading size="lg">Cambiar fecha de la solicitud</flux:heading>
            <x-date-picker label="Nueva fecha" wire:model.live="reprogramacion.fecha"
                min-date="{{ now()->format('Y-m-d') }}" />
            <flux:radio.group label="Horarios disponibles" wire:model="reprogramacion.hora" variant="pills">
                @forelse ($horariosReprogramacion as $horario)
                    <flux:radio
                        :label="\Carbon\Carbon::parse($horario)->format('h:i a')"
                        :value="$horario"
                    />
                @empty
                    <flux:text>
                        {{ $reprogramacion['fecha'] ? 'No hay horarios disponibles para esta fecha.' : 'Selecciona una fecha para ver los horarios disponibles.' }}
                    </flux:text>
                @endforelse
            </flux:radio.group>
            @error('reprogramacion.hora')
                <flux:error>{{ $message }}</flux:error>
            @enderror
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Guardar nueva fecha</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="cancelar-cita-modal" flyout @close="resetData">
        <form wire:submit="cancelar" class="space-y-4">
            <flux:heading size="lg">Cancelar solicitud</flux:heading>
            <flux:textarea label="Motivo de cancelación" wire:model="motivoCancelacion" required />
            @error('motivoCancelacion')
                <flux:error>{{ $message }}</flux:error>
            @enderror
            <div class="flex justify-end">
                <flux:button type="submit" variant="danger">Confirmar cancelación</flux:button>
            </div>
        </form>
    </flux:modal>


</div>
