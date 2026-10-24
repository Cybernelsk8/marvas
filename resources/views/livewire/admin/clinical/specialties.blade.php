<section class="mx-auto w-full max-w-7xl space-y-6 p-0 lg:p-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Especialidades</flux:heading>
            <flux:text class="mt-2">Gestiona el catálogo de especialidades. El identificador URL debe ser único.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="resetForm" x-on:click="$flux.modal('specialty-form').show()">
            Nueva especialidad
        </flux:button>
    </header>

    <x-data-table :headers="$this->headers" :rows="$this->rows">
        @interact('activa', $row)
            <flux:badge :color="$row->activa ? 'green' : 'zinc'">
                {{ $row->activa ? 'Activa' : 'Inactiva' }}
            </flux:badge>
        @endinteract

        @interact('actions', $row)
            <flux:dropdown>
                <flux:button size="sm" icon="ellipsis-vertical" class="cursor-pointer" variant="ghost" />
                <flux:menu>
                    <flux:menu.item icon="pencil-square" wire:click="edit({{ $row->id }})">Editar</flux:menu.item>
                    <flux:menu.item
                        :icon="$row->activa ? 'x-circle' : 'check-circle'"
                        :variant="$row->activa ? 'danger' : 'default'"
                        wire:click="toggleActive({{ $row->id }})"
                    >
                        {{ $row->activa ? 'Desactivar' : 'Activar' }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @endinteract
    </x-data-table>

    <flux:modal name="specialty-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">{{ $editingId ? 'Editar especialidad' : 'Nueva especialidad' }}</flux:heading>
            <flux:input label="Nombre *" wire:model.live.debounce.300ms="nombre" />
            <flux:error name="nombre" />
            <flux:input label="Slug *" wire:model="slug" />
            <flux:error name="slug" />
            <flux:textarea label="Descripción" wire:model="descripcion" />
            <flux:error name="descripcion" />
            <flux:input label="Color hexadecimal" placeholder="#2563EB" wire:model="color_hex" />
            <flux:error name="color_hex" />
            <flux:checkbox label="Especialidad activa" wire:model="activa" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button type="button" variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
