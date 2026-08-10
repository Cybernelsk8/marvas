<div class="dark:bg-zinc-800 rounded-xl">
    <flux:card>
        <div class="grid xl:grid-cols-3 gap-4 text-cyan-950">
            <div>
                <flux:radio.group
                    label="Elige el tipo de cita *"
                    variant="cards"
                    class="flex-col"
                    wire:model="cita.tipo"
                >
                    <flux:radio
                        value="primera_consulta"
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
                    wire:model="cita.fecha"
                    :min-date="now()->format('Y-m')"
                    :max-date="now()->addMonths(3)->format('Y-m-d')"
                    size="md"
                />
                <div class="py-5">
                    <flux:radio.group
                        label="Selecciona la hora de tu cita *"
                        wire:model="cita.hora"
                        variant="pills"
                        class=""
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
                    label="Nombre completo *"
                    icon="user"
                    wire:model="cita.nombre"
                    placeholder="Nombre completo"
                    required
                />
                <flux:input
                    label="Teléfono *"
                    icon="phone"
                    wire:model="cita.telefono"
                    type="tel"
                    mask="9999-9999"
                    maxlength="9"
                    placeholder="0000-0000"
                    required
                />
                <flux:input
                    label="Correo electrónico "
                    icon="envelope"
                    wire:model="cita.email"
                    placeholder="email@dominio.com"
                />
                <flux:textarea
                    label="Motivo de consulta *"
                    wire:model="cita.mensaje"
                    placeholder="Mensaje (opcional)"
                />
                <flux:button
                    icon="calendar"
                    wire:click="agendarCita"
                    class="btn--primary w-full"
                >
                    Agendar cita
                </flux:button>
            </div>
        </div>

    </flux:card>
</div>
