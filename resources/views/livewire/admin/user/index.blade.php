<section class="w-full">

    @can('users.store')
        <div class="flex justify-center mb-4">
            <flux:modal.trigger name="newUser">
                <flux:button
                    icon="plus"
                    title="Crear nuevo usuario"
                    variant="primary"
                >
                    Crear nuevo usuario
                </flux:button>
            </flux:modal.trigger>
        </div>
    @endcan

    @can('users.list')
        <x-data-table
            :headers="$this->headers"
            :rows="$this->rows"
        >
            @interact('information.nombre_completo', $row)
                <div class="flex items-center gap-3">
                    <flux:avatar
                        circle
                        name="{{ $row->information->nombre_corto }}"
                        size="lg"
                        initials="{{ $row->information->initials }}"
                        :src="$row->url_photo"
                    />
                    <div class="grid">
                        <span class="font-medium text-nowrap">
                            {{ $row->information->nombre_completo }}
                        </span>
                        <div class="flex gap-2 items-center">
                            <flux:icon.envelope class="size-4" />
                            <span class="text-xs opacity-60">
                                {{ $row->information->correo }}
                            </span>
                        </div>
                        <div class="flex gap-2 items-center">
                            <flux:icon.phone class="size-4" />
                            <span class="text-xs opacity-60">
                                {{ $row->information->telefono }}
                            </span>
                        </div>
                    </div>
                </div>
            @endinteract

            @interact('deleted_at', $row)
                @if ($row->deleted_at)
                    <flux:icon.x-circle class="size-5 text-red-500 mx-auto" />
                @else
                    <flux:icon.check-circle class="size-5 text-green-500 mx-auto" />
                @endif
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
                        @can('users.edit')
                            @if (!$row->deleted_at)
                                <flux:menu.item
                                    icon="pencil-square"
                                    :href="route('admin.users.show', $row->id)"
                                    wire:navigate
                                >
                                    Editar
                                </flux:menu.item>
                            @endif
                        @endcan
                        @can('users.restore')
                            @if ($row->deleted_at)
                                <flux:menu.item
                                    variant="danger"
                                    icon="check-circle"
                                    wire:click="userRestore({{ $row->id }})"
                                >
                                    Restaurar
                                </flux:menu.item>
                            @endif
                        @endcan
                    </flux:menu>
                </flux:dropdown>
            @endinteract
        </x-data-table>
    @endcan

    <flux:modal
        name="newUser"
        flyout
        @close="resetData"
    >
        <form
            wire:submit.prevent="store"
            class="space-y-6"
        >
            <flux:heading size="lg">Nuevo usuario</flux:heading>

            <div class="grid grid-cols-6 gap-4">
                <div class="col-span-6">
                    <flux:input
                        wire:model="user.information.cui"
                        label="Dpi *"
                        icon="identification"
                        required
                    />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <flux:input
                        wire:model="user.information.nombres"
                        label="Nombres *"
                        type="text"
                        icon="pencil-square"
                        required
                    />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <flux:input
                        wire:model="user.information.apellidos"
                        label="Apellidos *"
                        icon="pencil-square"
                        type="text"
                        required
                    />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <x-date-picker
                        label="Fecha de nacimiento *"
                        wire:model="user.information.fecha_nacimiento"
                        required
                    />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <flux:radio.group
                        wire:model="user.information.sexo"
                        label="Seleccione sexo *"
                        required
                    >
                        <flux:radio
                            value="F"
                            label="Femenino"
                        />
                        <flux:radio
                            value="M"
                            label="Masculino"
                        />
                    </flux:radio.group>
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <flux:input
                        wire:model="user.information.telefono"
                        label="Teléfono *"
                        type="tel"
                        maxlength="9"
                        placeholder="5555-5555"
                        mask="9999-9999"
                        icon="phone"
                        required
                    />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <flux:input
                        wire:model.live="user.information.correo"
                        label="Correo *"
                        type="email"
                        icon="envelope"
                        placeholder="correo@ejemplo.com"
                        required
                    />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <x-select
                        label="Departamentos *"
                        wire:model.live="departamento_id"
                        :options="$this->departamentos->map(
                            fn($departamento) => ['value' => $departamento->id, 'label' => $departamento->nombre],
                        )"
                        placeholder="Seleccione departamento"
                        icon="map"
                    />

                </div>
                <div class="col-span-6 sm:col-span-3">
                    <x-select
                        label="Municipios *"
                        wire:model.live="municipio_id"
                        :options="$municipios->map(
                            fn($municipio) => ['value' => $municipio->id, 'label' => $municipio->nombre],
                        )"
                        placeholder="Seleccione municipio"
                        icon="building-2"
                    />
                </div>
                <div class="col-span-6 sm:col-span-3">

                    <flux:select
                        label="Zonas *"
                        wire:model.live="user.information.domicilio.zona_id"
                        placeholder="Seleccione zona"
                        icon="map-pin"
                        :disabled="$municipio_id != 76"
                    >
                        <flux:select.option value=""> -- Seleccione zona -- </flux:select.option>
                        @forelse (range(1,25) as $zona)
                            <flux:select.option value="{{ $zona }}">
                                {{ 'Zona ' . $zona }}
                            </flux:select.option>
                        @empty
                            <flux:select.option>No hay data</flux:select.option>
                        @endforelse
                    </flux:select>
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <flux:input
                        wire:model="user.information.domicilio.colonia"
                        label="Colonia"
                        icon="signpost"
                        required
                    />
                </div>
                <div class="col-span-6">
                    <flux:input
                        wire:model="user.information.domicilio.direccion"
                        label="Dirección *"
                        icon="map-pinned"
                        required
                    />
                </div>
                <div class="col-span-6 sm:col-span-3">

                    <x-select
                        label="Areas"
                        wire:model.live="user.area_id"
                        :options="$this->areas->map(fn($area) => ['value' => $area->id, 'label' => $area->name])"
                        placeholder="Seleccione área"
                        icon="building-office"
                    />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <x-select
                        label="Roles"
                        wire:model.live="user.role"
                        :options="$this->roles->map(fn($role) => ['value' => $role->name, 'label' => $role->name])"
                        placeholder="Seleccione rol"
                        icon="tag"
                    />
                </div>
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

    <flux:modal name="restaurar-usuario">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">¿Restaurar usuario?</flux:heading>

                <flux:text class="mt-2">
                    Estás a punto de restaurar este usuario.<br>
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button
                    wire:click="restore"
                    variant="danger"
                >Sí, restaurar</flux:button>
            </div>
        </div>
    </flux:modal>

</section>
