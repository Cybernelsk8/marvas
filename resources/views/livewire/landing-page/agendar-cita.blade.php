<div class="dark:bg-zinc-800 rounded-xl">
    <flux:card>
        <form
            wire:submit.prevent="store"
            class="grid xl:grid-cols-2 gap-4 text-cyan-950"
        >
            <div class="space-y-4">
                <x-calendar
                    wire:key="cita-fecha"
                    label="Selecciona la fecha de tu cita *"
                    wire:model.live="fecha"
                    :events="$eventos"
                    :disabled-dates="$fechasLlenas"
                    :min-date="now()->format('Y-m-d')"
                    :max-date="now()->addMonths(3)->format('Y-m-d')"
                    size="md"
                />
                <div class="py-5">

                    <flux:radio.group
                        label="Selecciona la hora de tu cita *"
                        wire:model="hora"
                        variant="pills"
                        required
                    >
                        @forelse($horarios as $horario)
                            <flux:radio
                                label="{{ \Carbon\Carbon::parse($horario)->format('h:i a') }}"
                                value="{{ \Carbon\Carbon::parse($horario)->format('H:i') }}"
                                class="cursor-pointer"
                            />
                        @empty
                            {{ $fecha ? 'No hay horarios disponibles para esta fecha.' : 'Selecciona una fecha para ver los horarios disponibles.' }}
                        @endforelse

                    </flux:radio.group>
                </div>
            </div>
            <div class="space-y-4">
                <flux:input
                    label="Nombres"
                    icon="user"
                    wire:model="nombres"
                    placeholder="Nombres"
                    required
                />
                <flux:input
                    label="Apellidos"
                    icon="user"
                    wire:model="apellidos"
                    placeholder="Apellidos"
                    required
                />
                <flux:input
                    label="Teléfono *"
                    icon="phone"
                    wire:model="telefono"
                    type="tel"
                    mask="9999-9999"
                    maxlength="9"
                    placeholder="0000-0000"
                    required
                />
                <flux:input
                    label="Correo electrónico "
                    icon="envelope"
                    wire:model="email"
                    placeholder="email@dominio.com"
                />
                <flux:textarea
                    label="Motivo de consulta *"
                    wire:model="mensaje"
                    placeholder="Mensaje (opcional)"
                />
                <flux:button
                    type="submit"
                    icon="calendar"
                    class="btn--primary w-full"
                >
                    Solicitar cita
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
