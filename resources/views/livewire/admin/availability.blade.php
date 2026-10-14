<section class="mx-auto w-full max-w-4xl p-6">
    <div class="mb-6">
        <flux:heading size="xl">Disponibilidad global</flux:heading>
        <flux:text class="mt-2">
            Configura las horas de inicio permitidas para citas en toda la clínica. Cada cita ocupa una hora.
            Los cambios aplican a nuevas reservas y reprogramaciones; no modifican citas existentes.
        </flux:text>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:card>
            <div class="space-y-4">
                @forelse ($hours as $index => $hour)
                    <div wire:key="availability-hour-{{ $index }}" class="flex items-end gap-3">
                        <flux:input
                            label="Hora de inicio"
                            type="time"
                            wire:model="hours.{{ $index }}"
                            :disabled="!auth()->user()->can('schedule.edit')"
                        />
                        @can('schedule.edit')
                            <flux:button
                                type="button"
                                variant="danger"
                                icon="trash"
                                aria-label="Eliminar horario"
                                wire:click="removeHour({{ $index }})"
                            />
                        @endcan
                    </div>
                    @error("hours.$index")
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                @empty
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        <flux:callout.heading>No hay horarios configurados</flux:callout.heading>
                        <flux:callout.text>
                            Agrega al menos una hora para habilitar reservas y reprogramaciones.
                        </flux:callout.text>
                    </flux:callout>
                @endforelse

                @error('hours')
                    <flux:error>{{ $message }}</flux:error>
                @enderror

                @can('schedule.edit')
                    <flux:button type="button" variant="ghost" icon="plus" wire:click="addHour">
                        Agregar horario
                    </flux:button>
                @endcan
            </div>
        </flux:card>

        @can('schedule.edit')
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                    Guardar horarios
                </flux:button>
            </div>
        @endcan
    </form>
</section>
