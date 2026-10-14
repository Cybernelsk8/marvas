<section class="mx-auto w-full max-w-7xl space-y-6 p-6">
    <header>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">Directorio de pacientes</flux:heading>
                <flux:text class="mt-2">Busca por nombre, DPI, teléfono o correo. Los datos clínicos solo se muestran
                    en la ficha autorizada.</flux:text>
            </div>
            @can('patients.create')
                <flux:button variant="primary" icon="plus" wire:click="createPatient">
                    Nuevo paciente
                </flux:button>
            @endcan
        </div>
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
            <flux:dropdown>
                <flux:button size="sm" icon="ellipsis-vertical" class="cursor-pointer" variant="ghost" />
                <flux:menu>
                    <flux:menu.item icon="eye" :href="route('admin.patients.show', $row)" wire:navigate>
                        Ver ficha
                    </flux:menu.item>
                    @can('patients.edit')
                        <flux:menu.item icon="pencil-square" wire:click="editPatient({{ $row->id }})">
                            Editar
                        </flux:menu.item>
                    @endcan
                    @can('patients.merge')
                        @if ($row->activo && ! $row->fusionado_en_paciente_id)
                            <flux:menu.item icon="arrow-path-rounded-square" wire:click="startPatientMerge({{ $row->id }})">
                                Fusionar expediente
                            </flux:menu.item>
                        @endif
                    @endcan
                </flux:menu>
            </flux:dropdown>
        @endinteract
    </x-data-table>

    <flux:modal name="patient-form" class="w-full max-w-3xl">
        <form wire:submit="savePatient" class="space-y-5">
            <flux:heading size="lg">
                {{ $editingPatientId ? 'Editar paciente' : 'Nuevo paciente' }}
            </flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input label="Nombres *" wire:model.live.debounce.400ms="patientForm.nombres" required />
                <flux:input label="Apellidos *" wire:model.live.debounce.400ms="patientForm.apellidos" required />
                <flux:input label="DPI" wire:model.live.debounce.400ms="patientForm.numero_dpi" />
                <flux:input label="Teléfono" wire:model.live.debounce.400ms="patientForm.telefono" />
                <flux:input label="Correo" type="email" wire:model.live.debounce.400ms="patientForm.email" />
                <flux:input label="Fecha de nacimiento" type="date" wire:model="patientForm.fecha_nacimiento" />
                <flux:select label="Sexo *" wire:model="patientForm.sexo" required>
                    <flux:select.option value="otro">No especificado</flux:select.option>
                    <flux:select.option value="F">Femenino</flux:select.option>
                    <flux:select.option value="M">Masculino</flux:select.option>
                </flux:select>
                <flux:select label="Estado civil" wire:model="patientForm.estado_civil">
                    <flux:select.option value="">No especificado</flux:select.option>
                    <flux:select.option value="soltero">Soltero/a</flux:select.option>
                    <flux:select.option value="casado">Casado/a</flux:select.option>
                    <flux:select.option value="divorciado">Divorciado/a</flux:select.option>
                    <flux:select.option value="viudo">Viudo/a</flux:select.option>
                    <flux:select.option value="union_libre">Unión libre</flux:select.option>
                    <flux:select.option value="otro">Otro</flux:select.option>
                </flux:select>
                <flux:input label="Teléfono de emergencia" wire:model="patientForm.telefono_emergencia" />
                <flux:input label="Religión" wire:model="patientForm.religion" />
                <flux:input label="Ocupación" wire:model="patientForm.ocupacion" />
                <flux:input label="Cómo nos conoció" wire:model="patientForm.como_nos_conocio" />
                <div class="sm:col-span-2">
                    <flux:textarea label="Dirección" wire:model="patientForm.direccion" rows="2" />
                </div>
            </div>

            @if ($errors->isNotEmpty())
                <div class="space-y-2" role="alert">
                    @foreach ($errors->all() as $message)
                        <flux:error>{{ $message }}</flux:error>
                    @endforeach
                </div>
            @endif

            @if ($this->possibleMatches->isNotEmpty())
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.heading>Posibles pacientes existentes</flux:callout.heading>
                    <flux:callout.text>
                        Revisa las coincidencias. Puedes abrir la ficha de una persona existente; no se fusionan
                        registros automáticamente.
                    </flux:callout.text>
                    <div class="mt-3 space-y-2">
                        @foreach ($this->possibleMatches as $match)
                            <div
                                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-300/40 p-3">
                                <div>
                                    <div class="font-medium">{{ $match->nombre_completo }}</div>
                                    <div class="text-sm">{{ $match->numero_dpi ?: 'Sin DPI' }} ·
                                        {{ $match->telefono ?: 'Sin teléfono' }} · {{ $match->email ?: 'Sin correo' }}
                                    </div>
                                </div>
                                <flux:button :href="route('admin.patients.show', $match)" size="sm" variant="ghost"
                                    wire:navigate>
                                    Abrir ficha
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                </flux:callout>

                @if (!$editingPatientId)
                    <flux:checkbox wire:model="confirmDistinctPatient"
                        label="Revisé las coincidencias y confirmo que esta persona es distinta." />
                @endif
            @endif

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" wire:click="resetPatientForm">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ $editingPatientId ? 'Guardar cambios' : 'Crear paciente y expediente' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    @can('patients.merge')
        <flux:modal name="patient-merge" class="w-full max-w-4xl">
            <div class="space-y-5">
                <flux:heading size="lg">Fusionar expedientes de pacientes</flux:heading>
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.heading>Esta acción no se puede deshacer desde el directorio</flux:callout.heading>
                    <flux:callout.text>
                        El paciente de origen quedará inactivo y vinculado al principal. Sus citas, consultas, planes,
                        pagos, archivos, consentimientos y otros registros pasarán al expediente principal.
                    </flux:callout.text>
                </flux:callout>

                @if ($mergeSourcePatientId)
                    @php($mergeSource = \App\Models\Paciente::query()->find($mergeSourcePatientId))
                    <flux:input label="Paciente de origen" :value="$mergeSource?->nombre_completo" readonly />
                @endif

                <flux:input
                    label="Buscar paciente principal (nombre, DPI, teléfono o correo)"
                    wire:model.live.debounce.400ms="mergeTargetSearch"
                    placeholder="Escribe al menos 2 caracteres"
                />
                @error('mergeTargetPatientId')
                    <flux:error>{{ $message }}</flux:error>
                @enderror

                @if ($mergeTargetSearch !== '' && mb_strlen(trim($mergeTargetSearch)) < 2)
                    <flux:text>Escribe al menos 2 caracteres para buscar pacientes.</flux:text>
                @elseif ($mergeTargetSearch !== '' && $this->mergeTargetCandidates->isEmpty())
                    <flux:text>No se encontraron pacientes activos para ese criterio.</flux:text>
                @endif

                @if ($this->mergeTargetCandidates->isNotEmpty())
                    <div class="max-h-56 space-y-2 overflow-y-auto">
                        @foreach ($this->mergeTargetCandidates as $candidate)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3">
                                <div>
                                    <div class="font-medium">{{ $candidate->nombre_completo }}</div>
                                    <div class="text-sm text-zinc-500">
                                        {{ $candidate->numero_dpi ?: 'Sin DPI' }} ·
                                        {{ $candidate->telefono ?: 'Sin teléfono' }} ·
                                        {{ $candidate->email ?: 'Sin correo' }}
                                    </div>
                                </div>
                                <flux:button
                                    type="button"
                                    size="sm"
                                    variant="{{ $mergeTargetPatientId === $candidate->id ? 'primary' : 'ghost' }}"
                                    wire:click="selectMergeTarget({{ $candidate->id }})"
                                >
                                    {{ $mergeTargetPatientId === $candidate->id ? 'Principal seleccionado' : 'Elegir como principal' }}
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($this->mergePreview)
                    <flux:separator />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:card>
                            <flux:heading size="sm">Origen</flux:heading>
                            <flux:text>{{ $this->mergePreview['source']->nombre_completo }}</flux:text>
                        </flux:card>
                        <flux:card>
                            <flux:heading size="sm">Principal</flux:heading>
                            <flux:text>{{ $this->mergePreview['target']->nombre_completo }}</flux:text>
                        </flux:card>
                    </div>

                    @if ($this->mergePreview['profileConflicts'] !== [] || $this->mergePreview['expedienteConflicts'] !== [])
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            <flux:callout.heading>Revisa los datos en conflicto</flux:callout.heading>
                            <flux:callout.text>
                                Para cada diferencia elige qué valor conservar. Los valores sin conflicto completarán los campos vacíos del paciente principal.
                            </flux:callout.text>
                        </flux:callout>

                        @foreach ($this->mergePreview['profileConflicts'] as $field => $conflict)
                            <flux:select
                                label="{{ $this->mergeProfileFieldLabel($field) }} — Principal: {{ $conflict['principal'] }} / Origen: {{ $conflict['origen'] }}"
                                wire:model="mergeProfileChoices.{{ $field }}"
                            >
                                <flux:select.option value="">Selecciona el valor que se conservará</flux:select.option>
                                <flux:select.option value="target">Conservar el dato del paciente principal</flux:select.option>
                                <flux:select.option value="source">Usar el dato del paciente de origen</flux:select.option>
                            </flux:select>
                            @error('mergeProfileChoices.'.$field)
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                        @endforeach

                        @foreach ($this->mergePreview['expedienteConflicts'] as $field => $conflict)
                            <flux:select
                                label="{{ $this->mergeProfileFieldLabel($field) }} — Principal: {{ $conflict['principal'] }} / Origen: {{ $conflict['origen'] }}"
                                wire:model="mergeExpedienteChoices.{{ $field }}"
                            >
                                <flux:select.option value="">Selecciona el valor que se conservará</flux:select.option>
                                <flux:select.option value="target">Conservar el dato del expediente principal</flux:select.option>
                                <flux:select.option value="source">Usar el dato del expediente de origen</flux:select.option>
                            </flux:select>
                            @error('mergeExpedienteChoices.'.$field)
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                        @endforeach
                    @else
                        <flux:callout variant="info" icon="information-circle">
                            <flux:callout.text>No hay conflictos entre los perfiles ni los resúmenes de expediente.</flux:callout.text>
                        </flux:callout>
                    @endif

                    <flux:callout variant="info" icon="clipboard-document-list">
                        <flux:callout.heading>Historial que se trasladará</flux:callout.heading>
                        <flux:callout.text>
                            Citas: {{ $this->mergePreview['counts']['citas'] }},
                            consultas: {{ $this->mergePreview['counts']['consultas'] }},
                            planes: {{ $this->mergePreview['counts']['planes_tratamiento'] }},
                            pagos: {{ $this->mergePreview['counts']['pagos'] }},
                            archivos: {{ $this->mergePreview['counts']['archivos_clinicos'] }},
                            facturaciones: {{ $this->mergePreview['counts']['datos_facturacion'] }}.
                        </flux:callout.text>
                    </flux:callout>

                    <flux:checkbox
                        wire:model="confirmPatientMerge"
                        label="Confirmo que estos dos registros pertenecen a la misma persona y revisé los valores que se conservarán."
                    />
                    @error('confirmPatientMerge')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror

                    <div class="flex justify-end gap-3">
                        <flux:modal.close>
                            <flux:button type="button" variant="ghost" wire:click="resetMergeForm">Cancelar</flux:button>
                        </flux:modal.close>
                        <flux:button
                            type="button"
                            variant="danger"
                            icon="arrow-path-rounded-square"
                            wire:click="mergePatientsManually"
                        >
                            Fusionar expedientes
                        </flux:button>
                    </div>
                @endif
            </div>
        </flux:modal>
    @endcan
</section>
