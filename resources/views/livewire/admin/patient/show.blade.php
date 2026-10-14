<section class="mx-auto w-full max-w-6xl space-y-6 p-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $patient->nombre_completo }}</flux:heading>
            <flux:text class="mt-2">Ficha del paciente</flux:text>
        </div>
        <flux:button :href="route('admin.patients.index')" variant="ghost" icon="arrow-left" wire:navigate>
            Volver al directorio
        </flux:button>
    </header>

    <div class="grid gap-6 lg:grid-cols-2">
        <flux:card class="space-y-4">
            <flux:heading size="lg">Datos del paciente</flux:heading>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-zinc-500">DPI</dt>
                    <dd>{{ $patient->numero_dpi ?: 'No registrado' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">Fecha de nacimiento</dt>
                    <dd>{{ $patient->fecha_nacimiento?->format('d/m/Y') ?: 'No registrada' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">Teléfono</dt>
                    <dd>{{ $patient->telefono ?: 'No registrado' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">Teléfono de emergencia</dt>
                    <dd>{{ $patient->telefono_emergencia ?: 'No registrado' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">Correo</dt>
                    <dd>{{ $patient->email ?: 'No registrado' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">Estado</dt>
                    <dd>{{ $patient->activo ? 'Activo' : 'Inactivo' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm text-zinc-500">Dirección</dt>
                    <dd>{{ $patient->direccion ?: 'No registrada' }}</dd>
                </div>
            </dl>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">Expediente maestro</flux:heading>
            @if ($patient->expediente)
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-zinc-500">Estado</dt>
                        <dd>{{ ucfirst($patient->expediente->estado) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Clasificación</dt>
                        <dd>{{ str($patient->expediente->clasificacion)->replace('_', ' ')->ucfirst() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Fase terapéutica</dt>
                        <dd>{{ str($patient->expediente->fase_terapeutica)->replace('_', ' ')->ucfirst() }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm text-zinc-500">Objetivo principal</dt>
                        <dd>{{ $patient->expediente->objetivo_principal ?: 'No definido' }}</dd>
                    </div>
                </dl>
            @else
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.heading>Sin expediente maestro</flux:callout.heading>
                    <flux:callout.text>Este registro aún no tiene un expediente asociado.</flux:callout.text>
                </flux:callout>
            @endif
        </flux:card>
    </div>

    <flux:card class="space-y-4">
        <flux:heading size="lg">Citas</flux:heading>
        <flux:table :paginate="$appointments">
            <flux:table.columns>
                <flux:table.column>Fecha y hora</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Sede</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($appointments as $appointment)
                    <flux:table.row :key="$appointment->id">
                        <flux:table.cell>{{ $appointment->fecha_hora_inicio->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ str($appointment->tipo_cita)->replace('_', ' ')->ucfirst() }}</flux:table.cell>
                        <flux:table.cell>{{ str($appointment->estado)->replace('_', ' ')->ucfirst() }}</flux:table.cell>
                        <flux:table.cell>{{ $appointment->sucursal?->nombre ?: 'Sin asignar' }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">
                            <div class="py-8 text-center">No hay citas registradas.</div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</section>
