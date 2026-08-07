<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern
                    class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern
                    class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern
                    class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />

            <div>
                <flux:radio.group
                    label="Elige el tipo de cita *"
                    variant="cards"
                    class="flex-col"
                    wire:model="cita.tipo"
                >
                    <flux:radio
                        value="consulta"
                        label="Consulta"
                        description="Primera cita para diagnostico y evaluación"
                    />
                    <flux:radio
                        value="seguimiento"
                        label="Seguimiento"
                        description="Cita para seguimiento de tratamiento"
                    />
                    <flux:radio
                        value="masaje"
                        label="Masaje"
                        description="Sesión de masaje relajante o terapéutico"
                    />
                </flux:radio.group>
            </div>

            <flux:input
                label="Nombre completo *"
                icon="user"
                wire:model="cita.nombre"
                placeholder="Nombre completo"
                required
            />

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />
        </div>
    </div>
</x-layouts::app>
