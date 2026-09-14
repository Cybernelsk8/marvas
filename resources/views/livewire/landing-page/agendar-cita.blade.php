<div class="dark:bg-zinc-800 rounded-xl">
    <flux:card>
        <form
            wire:submit.prevent="store"
            class="grid xl:grid-cols-3 gap-4 text-cyan-950"
        >
            <div>
                <flux:radio.group
                    label="Elige el tipo de cita *"
                    variant="cards"
                    class="flex-col"
                    wire:model="tipo"
                    required
                >
                    <flux:radio
                        value="primera_vez"
                        label="Primer consulta"
                        description="Primera cita para diagnostico y evaluación"
                        class="cursor-pointer"
                    />
                    <flux:radio
                        value="seguimiento"
                        label="Seguimiento"
                        description="Cita para seguimiento de tratamiento"
                        class="cursor-pointer"
                    />
                </flux:radio.group>
            </div>
            <div class="space-y-4">
                <x-calendar
                    wire:key="cita-fecha"
                    label="Selecciona la fecha de tu cita *"
                    wire:model="fecha"
                    :min-date="now()->format('Y-m')"
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
                        <flux:radio
                            label="09:00 am"
                            value="09:00"
                            class="cursor-pointer"
                        />
                        <flux:radio
                            label="11:00 am"
                            value="11:00"
                            class="cursor-pointer"
                        />
                        <flux:radio
                            label="03:00 pm"
                            value="15:00"
                            class="cursor-pointer"
                        />
                        <flux:radio
                            label="05:00 pm"
                            value="17:00"
                            class="cursor-pointer"
                        />

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
                    Agendar cita
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
