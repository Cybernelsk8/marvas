<div class="p-8">
    <x-calendar
        wire:model="range"
        mode="range"
        :months="2"
        :min-date="now()->format('Y-m-d')"
    />

    {{-- Fecha única --}}
    <x-date-picker
        label="Fecha de nacimiento"
        wire:model="birthdate"
        format="d/m/Y"
    />

    {{-- Rango con 2 meses visibles --}}
    <x-date-picker
        label="Rango de reserva"
        wire:model="range"
        mode="range"
        :months="2"
        :min-date="now()->format('Y-m-d')"
    />

    {{-- Múltiples fechas, con días bloqueados --}}
    <x-date-picker
        label="Días festivos"
        wire:model="holidays"
        mode="multiple"
        :disabled-days-of-week="[0, 6]"
        :disabled-dates="['2026-12-25']"
        :disabled-ranges="[['2026-08-05', '2026-08-10']]"
    />
    <br>
    <x-select
        wire:model.live="selected"
        searchable
        multiple
        :$options
    />


    <x-data-table
        :$headers
        :rows="$this->rows"
    >
    </x-data-table>

    <flux:button wire:click="getToast">
        Toast
    </flux:button>
</div>
