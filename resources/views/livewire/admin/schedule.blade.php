<section class="mx-auto w-full max-w-7xl space-y-6 p-0 lg:p-6">
    <header>
        <flux:heading size="xl">Agenda de citas</flux:heading>
        <flux:text class="mt-2">
            Selecciona un día para consultar sus citas. El calendario muestra citas de los últimos 3 meses
            y los próximos 6 meses; la disponibilidad es global para toda la clínica.
        </flux:text>
    </header>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="chevron-left" aria-label="Periodo anterior"
                wire:click="navigatePeriod(-1)" />
            <flux:button variant="ghost" wire:click="goToToday">Hoy</flux:button>
            <flux:button variant="ghost" icon="chevron-right" aria-label="Periodo siguiente"
                wire:click="navigatePeriod(1)" />
        </div>
        <flux:button.group>
            <flux:button variant="{{ $viewMode === 'day' ? 'primary' : 'ghost' }}" wire:click="setViewMode('day')">Día
            </flux:button>
            <flux:button variant="{{ $viewMode === 'week' ? 'primary' : 'ghost' }}" wire:click="setViewMode('week')">
                Semana</flux:button>
            <flux:button variant="{{ $viewMode === 'month' ? 'primary' : 'ghost' }}" wire:click="setViewMode('month')">
                Mes</flux:button>
        </flux:button.group>
    </div>

    <div class="grid gap-6 {{ $viewMode === 'month' ? 'lg:grid-cols-[minmax(0,1.2fr)_minmax(20rem,0.8fr)]' : '' }}">
        @if ($viewMode === 'month')
            <flux:card>
                <x-calendar label="Calendario mensual" :events="$events" event-name="calendar-day-selected"
                    initial-month="{{ substr($selectedDate, 0, 7) }}" size="auto" min-size="md" max-preview="2" />
            </flux:card>
            @error('selectedDate')
                <flux:error>{{ $message }}</flux:error>
            @enderror
        @endif

        <flux:card class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">
                        @if ($viewMode === 'week')
                            Semana del {{ \Carbon\Carbon::parse($selectedDate)->startOfWeek()->format('d/m') }}
                            al {{ \Carbon\Carbon::parse($selectedDate)->endOfWeek()->format('d/m/Y') }}
                        @else
                            {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('j \d\e F Y') }}
                        @endif
                    </flux:heading>
                    <flux:text>{{ $this->appointments->count() }} citas</flux:text>
                </div>
                <div class="flex flex-wrap gap-3">
                    <flux:select label="Sede" wire:model.live="sucursalFiltro" class="min-w-48">
                        <flux:select.option value="todas">Todas las sedes</flux:select.option>
                        <flux:select.option value="sin_sede">Sin sede asignada</flux:select.option>
                        @foreach ($sucursales as $sucursal)
                            <flux:select.option value="{{ $sucursal->id }}">
                                {{ $sucursal->nombre }}{{ $sucursal->activa ? '' : ' (inactiva)' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Profesional" wire:model.live="profesionalFiltro" class="min-w-48">
                        <flux:select.option value="todos">Todos los profesionales</flux:select.option>
                        <flux:select.option value="sin_asignar">Sin profesional asignado</flux:select.option>
                        @foreach ($profesionalesFiltro as $profesional)
                            <flux:select.option value="{{ $profesional->id }}">
                                {{ $profesional->nombre_completo }}{{ $profesional->activo ? '' : ' (inactivo)' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Estado" wire:model.live="status" class="min-w-44">
                        <flux:select.option value="activa">Activas</flux:select.option>
                        <flux:select.option value="todas">Todos los estados</flux:select.option>
                        @foreach ($statuses as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <flux:separator />

            <div class="max-h-[38rem] space-y-5 overflow-y-auto">
                @foreach ($appointmentDays as $date => $appointments)
                    <section wire:key="schedule-day-{{ $date }}" class="space-y-3">
                        @if ($viewMode === 'week')
                            <flux:heading size="sm" class="border-b border-zinc-200 pb-2 dark:border-zinc-700">
                                {{ \Carbon\Carbon::parse($date)->translatedFormat('l j \d\e F') }}
                            </flux:heading>
                        @endif

                        @forelse ($appointments as $appointment)
                            <article wire:key="schedule-appointment-{{ $appointment->id }}"
                                class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <flux:heading size="sm">
                                            {{ $appointment->fecha_hora_inicio->format('H:i') }}–{{ $appointment->fecha_hora_fin->format('H:i') }}
                                        </flux:heading>
                                        <flux:text class="mt-1 font-medium">
                                            {{ $appointment->expediente->paciente->nombre_completo }}
                                        </flux:text>
                                    </div>
                                    <flux:badge
                                        :color="match ($appointment->estado) {
                                                                                                                                                                                                                                                                                                                                                                                                                    'solicitada' => 'amber',
                                                                                                                                                                                                                                                                                                                                                                                                                    'confirmada' => 'green',
                                                                                                                                                                                                                                                                                                                                                                                                                    'agendada' => 'blue',
                                                                                                                                                                                                                                                                                                                                                                                                                    'en_atencion' => 'purple',
                                                                                                                                                                                                                                                                                                                                                                                                                    'finalizada' => 'zinc',
                                                                                                                                                                                                                                                                                                                                                                                                                    'cancelada', 'no_asistio' => 'red',
                                                                                                                                                                                                                                                                                                                                                                                                                    'reprogramada' => 'cyan',
                                                                                                                                                                                                                                                                                                                                                                                                                    default => 'zinc',
                                                                                                                                                                                                                                                                                                                                                                                                                }">
                                        {{ $statuses[$appointment->estado] ?? $appointment->estado }}
                                    </flux:badge>
                                </div>

                                <div class="mt-3 grid gap-1 text-sm text-zinc-600 dark:text-zinc-300">
                                    <p><strong>Tipo:</strong>
                                        {{ str_replace('_', ' ', ucfirst($appointment->tipo_cita)) }}</p>
                                    <p><strong>Teléfono:</strong>
                                        {{ $appointment->expediente->paciente->telefono ?: 'No registrado' }}</p>
                                    <p><strong>Profesional:</strong>
                                        {{ $appointment->profesional?->nombre_completo ?? 'Sin asignar' }}</p>
                                    <p><strong>Sede:</strong> {{ $appointment->sucursal?->nombre ?? 'Sin asignar' }}
                                    </p>
                                    @if ($appointment->especialidad)
                                        <p><strong>Especialidad:</strong> {{ $appointment->especialidad->nombre }}</p>
                                    @endif
                                    @if ($appointment->notas_recepcion)
                                        <p><strong>Motivo:</strong> {{ $appointment->notas_recepcion }}</p>
                                    @endif
                                </div>

                                @if (
                                    (in_array($appointment->estado, ['solicitada', 'reprogramada', 'agendada'], true) &&
                                        auth()->user()->can('requests.confirm')) ||
                                        ($appointment->estado === 'solicitada' &&
                                            (auth()->user()->can('requests.reschedule') || auth()->user()->can('requests.cancel'))))
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        @if (in_array($appointment->estado, ['solicitada', 'reprogramada', 'agendada'], true) &&
                                                auth()->user()->can('requests.confirm'))
                                            <flux:button size="sm" variant="primary" icon="check"
                                                wire:click="prepareConfirmation({{ $appointment->id }})">
                                                Confirmar
                                            </flux:button>
                                        @endif
                                        @if ($appointment->estado === 'solicitada' && auth()->user()->can('requests.reschedule'))
                                            <flux:button size="sm" variant="ghost" icon="calendar"
                                                wire:click="prepareRescheduling({{ $appointment->id }})">
                                                Reprogramar
                                            </flux:button>
                                        @endif
                                        @if ($appointment->estado === 'solicitada' && auth()->user()->can('requests.cancel'))
                                            <flux:button size="sm" variant="ghost" icon="x-mark"
                                                wire:click="prepareCancellation({{ $appointment->id }})">
                                                Cancelar
                                            </flux:button>
                                        @endif
                                    </div>
                                @endif
                            </article>
                        @empty
                            <div class="py-4 text-center text-sm text-zinc-500">
                                No hay citas para esta fecha y estado.
                            </div>
                        @endforelse
                    </section>
                @endforeach
            </div>
        </flux:card>
    </div>

    <flux:modal name="agenda-confirmar-cita-modal" class="max-w-lg">
        <form wire:submit="confirmAppointment" class="space-y-4">
            <flux:heading size="lg">Confirmar cita</flux:heading>
            <flux:text>Asigna la sede donde se atenderá y confirma la fecha y hora.</flux:text>
            @if ($sucursales->where('activa', true)->isEmpty())
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.heading>No hay sedes activas</flux:callout.heading>
                    <flux:callout.text>Activa o registra una sede antes de confirmar citas.</flux:callout.text>
                </flux:callout>
            @else
                <flux:select label="Sede de atención *" wire:model="sucursalConfirmacion"
                    placeholder="Selecciona la sede" required>
                    @foreach ($sucursales->where('activa', true) as $sucursal)
                        <flux:select.option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($sucursalConfirmacion)
                    @php($profesionalesDeSede = $profesionales ?: collect())
                    @if ($profesionalesDeSede->isEmpty())
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            <flux:callout.heading>Sin profesionales disponibles</flux:callout.heading>
                            <flux:callout.text>Asocia profesionales activos a la sede seleccionada antes de confirmar la
                                cita.</flux:callout.text>
                        </flux:callout>
                    @else
                        <flux:select label="Profesional de atención *" wire:model="profesionalConfirmacion"
                            placeholder="Selecciona el profesional" required>
                            @foreach ($profesionalesDeSede as $profesional)
                                <flux:select.option value="{{ $profesional->id }}">
                                    {{ $profesional->nombre_completo }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                @endif
            @endif
            @error('sucursalConfirmacion')
                <flux:error>{{ $message }}</flux:error>
            @enderror
            @error('profesionalConfirmacion')
                <flux:error>{{ $message }}</flux:error>
            @enderror
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Volver</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                    :disabled="$sucursales->where('activa', true)->isEmpty() || ! $sucursalConfirmacion || ($sucursalConfirmacion && ($profesionales ?? collect())->isEmpty())">
                    Confirmar cita
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="agenda-reprogramar-cita-modal" flyout>
        <form wire:submit="rescheduleAppointment" class="space-y-4">
            <flux:heading size="lg">Reprogramar cita</flux:heading>
            <x-date-picker label="Nueva fecha" wire:model="reprogramacion.fecha"
                min-date="{{ now()->format('Y-m-d') }}" />
            <flux:input label="Nueva hora" type="time" wire:model="reprogramacion.hora" />
            @error('reprogramacion.hora')
                <flux:error>{{ $message }}</flux:error>
            @enderror
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Volver</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    Guardar nueva fecha
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="agenda-cancelar-cita-modal" flyout>
        <form wire:submit="cancelAppointment" class="space-y-4">
            <flux:heading size="lg">Cancelar solicitud</flux:heading>
            <flux:textarea label="Motivo de cancelación" wire:model="motivoCancelacion" required />
            @error('motivoCancelacion')
                <flux:error>{{ $message }}</flux:error>
            @enderror
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Volver</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" wire:loading.attr="disabled">
                    Confirmar cancelación
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
