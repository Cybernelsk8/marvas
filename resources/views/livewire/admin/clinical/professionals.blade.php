<section class="mx-auto w-full max-w-7xl space-y-6 p-0 lg:p-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Profesionales</flux:heading>
            <flux:text class="mt-2">Vincula cuentas existentes con sedes y especialidades. La cuenta, el rol y los
                permisos se administran desde Usuarios.</flux:text>
        </div>
        <flux:button
            variant="primary"
            icon="plus"
            wire:click="resetForm"
            x-on:click="$flux.modal('professional-form').show()"
        >
            Nuevo profesional
        </flux:button>
    </header>

    <x-data-table
        :headers="$this->headers"
        :rows="$this->rows"
    >
        @interact('nombre_completo', $row)
            <div class="flex items-center gap-2">
                <flux:avatar
                    :src="$row->user?->avatarUrl"
                    :name="$row->nombre_completo"
                    size="xl"
                    circle
                />
                <div class="flex flex-col">
                    <span class="font-medium">
                        {{ $row->nombre_completo ?? '' }}
                    </span>
                    <span class="text-sm text-zinc-500">
                        {{ $row->user?->email ?? 'Cuenta no disponible' }}
                    </span>
                    <span class="text-sm text-zinc-500">
                        {{ $row->cedula_profesional ?? '' }}
                    </span>
                </div>
            </div>
        @endinteract

        @interact('sucursales', $row)
            {{ $row->sucursales->pluck('nombre')->join(', ') ?: '' }}
        @endinteract

        @interact('especialidades', $row)
            {{ $row->especialidades->pluck('nombre')->join(', ') ?: '' }}
        @endinteract

        @interact('activo', $row)
            <flux:badge :color="$row->activo ? 'green' : 'zinc'">
                {{ $row->activo ? 'Activo' : 'Inactivo' }}
            </flux:badge>
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
                    <flux:menu.item
                        icon="pencil-square"
                        wire:click="edit({{ $row->id }})"
                    >
                        Editar
                    </flux:menu.item>
                    <flux:menu.item
                        :icon="$row->activo ? 'x-circle' : 'check-circle'"
                        :variant="$row->activo ? 'danger' : 'default'"
                        wire:click="toggleActive({{ $row->id }})"
                    >
                        {{ $row->activo ? 'Desactivar' : 'Activar' }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @endinteract
    </x-data-table>

    <flux:modal
        name="professional-form"
        class="max-w-2xl"
    >
        <form
            wire:submit="save"
            class="space-y-5"
        >
            <flux:heading size="lg">{{ $editingId ? 'Editar profesional' : 'Nuevo profesional' }}</flux:heading>
            <flux:select
                label="Cuenta existente *"
                wire:model.live="userId"
            >
                <flux:select.option value="">Selecciona una cuenta</flux:select.option>
                @foreach ($users as $user)
                    <flux:select.option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="userId" />
            @if ($users->isEmpty())
                <flux:callout variant="warning">
                    <flux:callout.text>No hay cuentas disponibles. Un Sysadmin debe crear la cuenta y asignar sus
                        permisos antes de vincularla.</flux:callout.text>
                </flux:callout>
            @endif
            <flux:input
                label="Nombre profesional *"
                wire:model="nombreCompleto"
            />
            <flux:error name="nombreCompleto" />
            <flux:input
                label="Cédula profesional"
                wire:model="cedulaProfesional"
            />
            <flux:error name="cedulaProfesional" />
            <flux:input
                label="Color de agenda"
                placeholder="#2563EB"
                wire:model="colorAgendaHex"
            />
            <flux:error name="colorAgendaHex" />

            <div class="space-y-2">
                <flux:heading size="sm">Sedes *</flux:heading>
                @foreach ($branches as $branch)
                    <flux:checkbox
                        wire:model.live="branchIds"
                        value="{{ $branch->id }}"
                        label="{{ $branch->nombre }}"
                    />
                @endforeach
                @error('branchIds')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
                @error('branchIds.*')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
            </div>
            <flux:select
                label="Sede principal *"
                wire:model="primaryBranchId"
            >
                <flux:select.option value="">Selecciona una sede</flux:select.option>
                @foreach ($branches->whereIn('id', $branchIds) as $branch)
                    <flux:select.option value="{{ $branch->id }}">{{ $branch->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="primaryBranchId" />

            <div class="space-y-2">
                <flux:heading size="sm">Especialidades *</flux:heading>
                @foreach ($specialties as $specialty)
                    <flux:checkbox
                        wire:model.live="specialtyIds"
                        value="{{ $specialty->id }}"
                        label="{{ $specialty->nombre }}"
                    />
                @endforeach
                @error('specialtyIds')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
                @error('specialtyIds.*')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
            </div>
            <flux:select
                label="Especialidad principal *"
                wire:model="primarySpecialtyId"
            >
                <flux:select.option value="">Selecciona una especialidad</flux:select.option>
                @foreach ($specialties->whereIn('id', $specialtyIds) as $specialty)
                    <flux:select.option value="{{ $specialty->id }}">{{ $specialty->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="primarySpecialtyId" />
            <flux:checkbox
                label="Profesional activo"
                wire:model="activo"
            />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button
                        type="button"
                        variant="ghost"
                    >Cancelar</flux:button>
                </flux:modal.close>
                <flux:button
                    type="submit"
                    variant="primary"
                >Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
