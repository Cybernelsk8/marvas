<section class="mx-auto w-full max-w-7xl space-y-6 p-6">
    <header>
        <flux:heading size="xl">Directorio de pacientes</flux:heading>
        <flux:text class="mt-2">Busca por nombre, DPI, teléfono o correo. Los datos clínicos solo se muestran en la
            ficha autorizada.</flux:text>
    </header>

    <div class="flex justify-end">
        <flux:select label="Estado" wire:model.live="status">
            <flux:select.option value="active">Activos</flux:select.option>
            <flux:select.option value="inactive">Inactivos</flux:select.option>
            <flux:select.option value="all">Todos</flux:select.option>
        </flux:select>
    </div>

    <x-data-table :headers="$this->headers" :rows="$this->rows">
        @interact('nombre_completo', $row)
            <div class="font-medium">{{ $row->nombre_completo }}</div>
        @endinteract

        @interact('activo', $row)
            <flux:badge :color="$row->activo ? 'green' : 'zinc'">
                {{ $row->activo ? 'Activo' : 'Inactivo' }}
            </flux:badge>
        @endinteract

        @interact('actions', $row)
            <flux:button :href="route('admin.patients.show', $row)" size="sm" variant="ghost" icon="eye"
                wire:navigate>
                Ver ficha
            </flux:button>
        @endinteract
    </x-data-table>
</section>
