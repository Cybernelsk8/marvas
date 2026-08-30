<section class="w-full">

    @can('areas.store')
        <div class="flex justify-center mb-4">
            <flux:modal.trigger name="newArea">
                <flux:button
                    icon="plus"
                    title="Crear nueva área"
                    variant="primary"
                >
                    Crear nueva área
                </flux:button>
            </flux:modal.trigger>
        </div>
    @endcan

    @can('areas.list')
        <x-data-table
            :headers="$this->headers"
            :rows="$this->rows"
        >
            @interact('deleted_at', $row)
                <flux:icon
                    :name="$row->deleted_at ? 'x-circle' : 'check-circle'"
                    :class="$row->deleted_at ? 'text-red-500' : 'text-green-500'"
                    class="size-5"
                />
            @endinteract

            @interact('actions', $row)
                <flux:dropdown>
                    <flux:button
                        size="sm"
                        icon="ellipsis-vertical"
                        class="cursor-pointer"
                        variant="ghost"
                    />
                    <flux:menu>
                        @can('areas.edit')
                            <flux:menu.item
                                icon="pencil-square"
                                wire:click="edit({{ $row->id }})"
                            >
                                Editar
                            </flux:menu.item>
                        @endcan

                        @can('areas.disabled')
                            <flux:menu.item
                                variant="danger"
                                icon="{{ $row->deleted_at ? 'check-circle' : 'x-circle' }}"
                                wire:click="disableItem({{ $row->id }})"
                            >
                                {{ $row->deleted_at ? 'Habilitar' : 'Desactivar' }}
                            </flux:menu.item>
                        @endcan

                        @can('areas.delete')
                            <flux:menu.item
                                icon="trash"
                                variant="danger"
                                wire:click="delete({{ $row->id }})"
                            >
                                Eliminar
                            </flux:menu.item>
                        @endcan
                    </flux:menu>
                </flux:dropdown>
            @endinteract
        </x-data-table>
    @endcan

    <flux:modal
        name="newArea"
        flyout
        @close="resetData"
    >
        <form
            wire:submit.prevent="store"
            class="space-y-6"
        >
            <flux:heading size="lg">Nueva área</flux:heading>
            <div class="grid gap-4">
                <flux:input
                    label="Nombre *"
                    wire:model="area.name"
                    icon="pencil-square"
                    placeholder="Nombre de la área"
                    autofocus
                    required
                />

                <flux:select
                    label="Pertenece a"
                    wire:model="area.area_id"
                >
                    <flux:select.option value="">-- Seleccione dependencia --</flux:select.option>
                    @forelse ($dependencies as $dependency)
                        <flux:select.option value="{{ $dependency->id }}">
                            {{ $dependency->id . ' - ' . $dependency->name }}
                        </flux:select.option>
                    @empty
                        <flux:select.option>No hay datos</flux:select.option>
                    @endforelse
                </flux:select>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button
                        icon="x-mark"
                        variant="ghost"
                    >Cancelar</flux:button>
                </flux:modal.close>

                <flux:button
                    type="submit"
                    icon="arrow-up-on-square-stack"
                    variant="danger"
                >Crear</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal
        name="editArea"
        flyout
        @close="resetData"
    >
        <form
            wire:submit.prevent="update"
            class="space-y-6"
        >
            <flux:heading size="lg">Editar área</flux:heading>

            <div class="grid gap-4">
                <flux:input
                    label="Nombre *"
                    icon="pencil-square"
                    wire:model="area.name"
                    placeholder="Nombre de la área"
                    autofocus
                    required
                />

                <flux:select
                    label="Pertenece a"
                    wire:model="area.area_id"
                >
                    <flux:select.option value="">-- Seleccione dependencia --</flux:select.option>
                    @forelse ($dependencies as $dependency)
                        <flux:select.option value="{{ $dependency->id }}">
                            {{ $dependency->id . ' - ' . $dependency->name }}
                        </flux:select.option>
                    @empty
                        <flux:select.option>No hay datos</flux:select.option>
                    @endforelse
                </flux:select>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button
                        icon="x-mark"
                        variant="ghost"
                    >Cancelar</flux:button>
                </flux:modal.close>

                <flux:button
                    type="submit"
                    icon="arrow-path"
                    variant="danger"
                >Actualizar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal
        name="deleteArea"
        @close="resetData"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Eliminar área?</flux:heading>

                <flux:text class="mt-2">
                    Está seguro de eliminar el área.<br>
                    <strong>{{ $this->area['name'] ?? '—' }}</strong>
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button
                        icon="x-mark"
                        variant="ghost"
                    >Cancelar</flux:button>
                </flux:modal.close>

                <flux:button
                    wire:click="destroy"
                    icon="trash"
                    variant="danger"
                >Sí, eliminar</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal
        name="disableArea"
        @close="resetData"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $this->area['deleted_at'] ? 'Habilitar área?' : 'Desactivar área?' }}
                </flux:heading>

                <flux:text class="mt-2">
                    {{ $this->area['deleted_at'] ? 'Está seguro de habilitar esta área.' : 'Está seguro de desactivar esta área.' }}<br>
                    <strong>{{ $this->area['name'] ?? '—' }}</strong>
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button
                        icon="x-mark"
                        variant="ghost"
                    >Cancelar</flux:button>
                </flux:modal.close>

                <flux:button
                    wire:click="disabled"
                    icon="{{ $this->area['deleted_at'] ? 'check-circle' : 'x-circle' }}"
                    variant="danger"
                >
                    {{ $this->area['deleted_at'] ? 'Sí, habilitar' : 'Sí, desactivar' }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

</section>
