<section class="mx-auto w-full max-w-4xl space-y-6 p-6">
    <header>
        <flux:heading size="xl">Disponibilidad global</flux:heading>
        <flux:text class="mt-2">
            Agrupa los días que comparten disponibilidad y agrega cada hora de inicio individualmente.
            Un día que no esté asignado a un grupo queda cerrado. La duración de una cita se configura para toda la clínica.
        </flux:text>
    </header>

    <form wire:submit="save" class="space-y-6">
        <div class="flex justify-end">
            @can('schedule.edit')
                <flux:button type="button" variant="ghost" icon="plus" wire:click="addGroup">
                    Agregar grupo de días
                </flux:button>
            @endcan
        </div>

        @forelse ($groups as $groupIndex => $group)
            <flux:card wire:key="availability-group-{{ $groupIndex }}" class="space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <flux:heading size="lg">Grupo de días {{ $groupIndex + 1 }}</flux:heading>
                    @can('schedule.edit')
                        <flux:button
                            type="button"
                            variant="danger"
                            icon="trash"
                            wire:click="removeGroup({{ $groupIndex }})"
                        >
                            Quitar grupo
                        </flux:button>
                    @endcan
                </div>

                <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-4">
                    @foreach ($dayOptions as $day => $label)
                        <flux:checkbox
                            wire:model="groups.{{ $groupIndex }}.days"
                            value="{{ $day }}"
                            label="{{ $label }}"
                            :disabled="!auth()->user()->can('schedule.edit')"
                        />
                    @endforeach
                </div>
                @error("groups.$groupIndex.days")
                    <flux:error>{{ $message }}</flux:error>
                @enderror
                @error("groups.$groupIndex.days.*")
                    <flux:error>{{ $message }}</flux:error>
                @enderror

                <flux:separator />
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <flux:heading size="sm">Horas de inicio</flux:heading>
                        <flux:text class="mt-1">Cada cita dura {{ $durationMinutes }} minutos. Deja un intervalo sin agregar si necesitas una pausa.</flux:text>
                    </div>
                    @can('schedule.edit')
                        <flux:button
                            type="button"
                            variant="ghost"
                            icon="plus"
                            wire:click="addHour({{ $groupIndex }})"
                        >
                            Agregar hora
                        </flux:button>
                    @endcan
                </div>

                @forelse ($group['hours'] as $hourIndex => $hour)
                    <div wire:key="availability-group-{{ $groupIndex }}-hour-{{ $hourIndex }}" class="flex items-end gap-3">
                        <flux:input
                            label="Hora de inicio"
                            type="time"
                            wire:model="groups.{{ $groupIndex }}.hours.{{ $hourIndex }}"
                            :disabled="!auth()->user()->can('schedule.edit')"
                        />
                        @can('schedule.edit')
                            <flux:button
                                type="button"
                                variant="danger"
                                icon="trash"
                                aria-label="Quitar hora"
                                wire:click="removeHour({{ $groupIndex }}, {{ $hourIndex }})"
                            />
                        @endcan
                    </div>
                    @error("groups.$groupIndex.hours.$hourIndex")
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                @empty
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        <flux:callout.heading>Este grupo no tiene horas</flux:callout.heading>
                        <flux:callout.text>Los días seleccionados quedarán cerrados hasta que agregues una hora.</flux:callout.text>
                    </flux:callout>
                @endforelse
                @error("groups.$groupIndex.hours")
                    <flux:error>{{ $message }}</flux:error>
                @enderror
            </flux:card>
        @empty
            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.heading>No hay horarios configurados</flux:callout.heading>
                <flux:callout.text>Todos los días permanecerán cerrados. Agrega un grupo y asígnale días y horas.</flux:callout.text>
            </flux:callout>
        @endforelse

        <flux:card class="space-y-4">
            <flux:heading size="lg">Duración de las citas</flux:heading>
            <flux:input
                label="Duración en minutos"
                type="number"
                min="15"
                max="1380"
                step="15"
                wire:model="durationMinutes"
                :disabled="!auth()->user()->can('schedule.edit')"
                required
            />
            <flux:text>Por defecto son 60 minutos. Usa múltiplos de 15; las horas de inicio deben dejar espacio para completar la duración.</flux:text>
            @error('durationMinutes')
                <flux:error>{{ $message }}</flux:error>
            @enderror
        </flux:card>

        <flux:card class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">Cierres excepcionales</flux:heading>
                    <flux:text class="mt-1">Agrega feriados o intervalos de vacaciones sin modificar el horario semanal.</flux:text>
                </div>
                @can('schedule.edit')
                    <flux:button type="button" variant="ghost" icon="plus" wire:click="addClosure">
                        Agregar cierre
                    </flux:button>
                @endcan
            </div>

            @if ($conflicts)
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.heading>No se guardaron los cambios</flux:callout.heading>
                    <flux:callout.text>Estas citas futuras quedarían fuera del horario solicitado. Reprograma las citas o ajusta los cambios y vuelve a guardar.</flux:callout.text>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($conflicts as $conflict)
                            <li>{{ $conflict['start'] }} — {{ $conflict['patient'] }} (cita #{{ $conflict['id'] }})</li>
                        @endforeach
                    </ul>
                </flux:callout>
            @endif

            @forelse ($closures as $index => $closure)
                <div wire:key="availability-closure-{{ $index }}" class="grid items-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700 sm:grid-cols-2">
                    <flux:input
                        label="Desde"
                        type="date"
                        min="{{ today()->toDateString() }}"
                        wire:model="closures.{{ $index }}.start_date"
                        :disabled="!auth()->user()->can('schedule.edit')"
                    />
                    <flux:input
                        label="Hasta"
                        type="date"
                        min="{{ today()->toDateString() }}"
                        wire:model="closures.{{ $index }}.end_date"
                        :disabled="!auth()->user()->can('schedule.edit')"
                    />
                    <flux:input
                        label="Motivo (opcional)"
                        wire:model="closures.{{ $index }}.reason"
                        :disabled="!auth()->user()->can('schedule.edit')"
                        class="sm:col-span-2"
                    />
                    @error("closures.$index.start_date")
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    @error("closures.$index.end_date")
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    @error("closures.$index.reason")
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    @can('schedule.edit')
                        <div class="sm:col-span-2">
                            <flux:button
                                type="button"
                                variant="danger"
                                icon="trash"
                                wire:click="removeClosure({{ $index }})"
                            >
                                Quitar cierre
                            </flux:button>
                        </div>
                    @endcan
                </div>
            @empty
                <flux:text>No hay cierres excepcionales registrados.</flux:text>
            @endforelse
        </flux:card>

        @can('schedule.edit')
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                    Guardar disponibilidad
                </flux:button>
            </div>
        @endcan
    </form>
</section>
