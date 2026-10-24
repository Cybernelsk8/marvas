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

    @if ($patient->fusionado_en_paciente_id && $patient->fusionadoEn)
        <flux:callout variant="warning" icon="arrow-uturn-right">
            <flux:callout.heading>Registro vinculado a otro paciente</flux:callout.heading>
            <flux:callout.text>
                Este registro fue inactivado al trasladar su historial. El expediente vigente pertenece a
                <a class="underline" href="{{ route('admin.patients.show', $patient->fusionadoEn) }}" wire:navigate>
                    {{ $patient->fusionadoEn->nombre_completo }}
                </a>.
            </flux:callout.text>
        </flux:callout>
    @endif

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
                    @can('patients.clinical.view')
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
                    @endcan
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
                @can('consultations.create')
                    <flux:table.column>Consulta</flux:table.column>
                @endcan
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($appointments as $appointment)
                    <flux:table.row :key="$appointment->id">
                        <flux:table.cell>{{ $appointment->fecha_hora_inicio->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ str($appointment->tipo_cita)->replace('_', ' ')->ucfirst() }}</flux:table.cell>
                        <flux:table.cell>{{ str($appointment->estado)->replace('_', ' ')->ucfirst() }}</flux:table.cell>
                        <flux:table.cell>{{ $appointment->sucursal?->nombre ?: 'Sin asignar' }}</flux:table.cell>
                        @can('consultations.create')
                            <flux:table.cell>
                                @if ($appointment->consulta)
                                    Consulta registrada
                                @elseif ($appointment->estado === 'confirmada')
                                    <flux:button
                                        size="sm"
                                        variant="primary"
                                        wire:click="startConsultation({{ $appointment->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="startConsultation({{ $appointment->id }})"
                                    >
                                        Iniciar consulta
                                    </flux:button>
                                @else
                                    —
                                @endif
                            </flux:table.cell>
                        @endcan
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="{{ auth()->user()->can('consultations.create') ? 5 : 4 }}">
                            <div class="py-8 text-center">No hay citas registradas.</div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        @error('appointment')
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.text>{{ $message }}</flux:callout.text>
            </flux:callout>
        @enderror
    </flux:card>

    @if ($clinicalRecords || $documents || $billing)
        <nav class="flex flex-wrap gap-3 border-b border-zinc-200 pb-3 dark:border-zinc-700" aria-label="Secciones del expediente">
            @if ($clinicalRecords)
                <a class="text-sm underline" href="#clinical-records">Información clínica</a>
            @endif
            @if ($documents)
                <a class="text-sm underline" href="#patient-documents">Documentos y consentimientos</a>
            @endif
            @if ($billing)
                <a class="text-sm underline" href="#patient-billing">Pagos y paquetes</a>
            @endif
        </nav>
    @endif

    @if ($clinicalRecords)
        <flux:card id="clinical-records" class="space-y-6">
            <flux:heading size="lg">Información clínica</flux:heading>

            <div class="space-y-3">
                <flux:heading size="base">Consultas</flux:heading>
                @forelse ($clinicalRecords['consultations'] as $consultation)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <p class="font-medium">
                            {{ $consultation->fecha_hora->format('d/m/Y H:i') }}
                            · {{ $consultation->especialidad?->nombre ?: 'Sin especialidad' }}
                        </p>
                        <p class="text-sm text-zinc-500">
                            {{ $consultation->profesional?->nombre_completo ?: 'Sin profesional' }}
                            · {{ str($consultation->estado)->replace('_', ' ')->ucfirst() }}
                            · Consulta {{ $consultation->numero_consulta }}
                        </p>
                        @php($evaluador = $consultation->anamnesisGeneral?->datos_formulario['resultado_evaluador'] ?? [])
                        @if (collect($evaluador)->contains(fn ($value) => filled($value)))
                            <div class="mt-3 space-y-2 rounded-md bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                                <p class="font-medium">07 · Resultado del evaluador</p>
                                @foreach ([
                                    'resumen_clinico' => 'Resumen clínico',
                                    'diagnostico_diferenciales' => 'Impresión diagnóstica',
                                    'objetivos_intervenciones_precauciones' => 'Objetivos y plan',
                                    'plan_seguimiento' => 'Seguimiento',
                                ] as $field => $label)
                                    @if (filled($evaluador[$field] ?? null))
                                        <p><strong>{{ $label }}:</strong> {{ $evaluador[$field] }}</p>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @php($mbi = $consultation->evaluacionIntegral)
                        @if ($mbi)
                            <div class="mt-3 space-y-2 rounded-md bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                                <p class="font-medium">Evaluación integral MBI</p>
                                @foreach ([
                                    'integracion_clinica' => 'Integración clínica',
                                    'hallazgos_principales' => 'Hallazgos principales',
                                    'objetivo_terapeutico' => 'Objetivo terapéutico',
                                    'especialidad_principal' => 'Especialidad principal',
                                    'observaciones_generales' => 'Observaciones',
                                ] as $field => $label)
                                    @if (filled($mbi->{$field}))
                                        <p><strong>{{ $label }}:</strong> {{ $mbi->{$field} }}</p>
                                    @endif
                                @endforeach
                                @if ($mbi->sistemas_comprometidos)
                                    <p><strong>Sistemas comprometidos:</strong> {{ implode(', ', $mbi->sistemas_comprometidos) }}</p>
                                @endif
                                @if ($mbi->especialidades_complementarias)
                                    <p><strong>Especialidades complementarias:</strong> {{ implode(', ', $mbi->especialidades_complementarias) }}</p>
                                @endif
                                @if ($mbi->modelo_6plus_seleccion)
                                    <p><strong>Selección Modelo 6+:</strong> {{ implode(', ', $mbi->modelo_6plus_seleccion) }}</p>
                                @endif
                            </div>
                        @endif
                        @can('consultations.edit')
                            <flux:button
                                class="mt-3"
                                size="sm"
                                variant="ghost"
                                :href="route('admin.consultations.show', $consultation)"
                                wire:navigate
                            >
                                {{ $consultation->anamnesisGeneral ? 'Ver o editar anamnesis' : 'Capturar anamnesis' }}
                            </flux:button>
                        @endcan
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No hay consultas registradas.</p>
                @endforelse
            </div>

            <div class="space-y-3">
                <flux:heading size="base">Planes de tratamiento</flux:heading>
                @forelse ($clinicalRecords['plans'] as $plan)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <p class="font-medium">
                            {{ $plan->clasificacion?->categoria
                                ? str($plan->clasificacion->categoria)->replace('_', ' ')->ucfirst()
                                : 'Plan de tratamiento' }}
                        </p>
                        <p>{{ $plan->diagnostico_integrativo }}</p>
                        <p class="text-sm text-zinc-500">
                            {{ str($plan->fase_actual)->replace('_', ' ')->ucfirst() }}
                            · {{ $plan->numero_sesiones }} sesiones
                            · {{ $plan->activo ? 'Activo' : 'Inactivo' }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No hay planes de tratamiento registrados.</p>
                @endforelse
            </div>
        </flux:card>
    @endif

    @if ($documents)
        <flux:card id="patient-documents" class="space-y-6">
            <flux:heading size="lg">Documentos y consentimientos</flux:heading>

            <div class="space-y-3">
                <flux:heading size="base">Archivos clínicos</flux:heading>
                @forelse ($documents['files'] as $file)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <p class="font-medium">{{ $file->nombre_original }}</p>
                        <p class="text-sm text-zinc-500">
                            {{ str($file->tipo_archivo)->replace('_', ' ')->ucfirst() }}
                            · {{ $file->created_at?->format('d/m/Y H:i') }}
                            @if ($file->descripcion)
                                · {{ $file->descripcion }}
                            @endif
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No hay archivos clínicos registrados.</p>
                @endforelse
            </div>

            <div class="space-y-3">
                <flux:heading size="base">Consentimientos</flux:heading>
                @forelse ($documents['consents'] as $consent)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <p class="font-medium">{{ $consent->plantilla?->titulo ?: 'Consentimiento' }}</p>
                        <p class="text-sm text-zinc-500">
                            {{ $consent->aceptado_at?->format('d/m/Y H:i') }}
                            · {{ $consent->acepta_voluntariamente ? 'Aceptado' : 'Pendiente' }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No hay consentimientos registrados.</p>
                @endforelse
            </div>
        </flux:card>
    @endif

    @if ($billing)
        <flux:card id="patient-billing" class="space-y-6">
            <flux:heading size="lg">Pagos y paquetes</flux:heading>

            <div class="space-y-3">
                <flux:heading size="base">Paquetes</flux:heading>
                @forelse ($billing['packages'] as $package)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <p class="font-medium">{{ $package->paquete?->nombre ?: 'Paquete' }}</p>
                        <p class="text-sm text-zinc-500">
                            {{ $package->estado }}
                            · {{ $package->sesiones_usadas }}/{{ $package->sesiones_totales }} sesiones
                            · Saldo pendiente: {{ number_format((float) $package->saldo_pendiente, 2) }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No hay paquetes registrados.</p>
                @endforelse
            </div>

            <div class="space-y-3">
                <flux:heading size="base">Pagos</flux:heading>
                @forelse ($billing['payments'] as $payment)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <p class="font-medium">{{ $payment->concepto }}</p>
                        <p class="text-sm text-zinc-500">
                            {{ $payment->pagado_at?->format('d/m/Y H:i') }}
                            · Pagado: {{ number_format((float) $payment->monto_pagado, 2) }}
                            · Saldo: {{ number_format((float) $payment->saldo, 2) }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No hay pagos registrados.</p>
                @endforelse
            </div>
        </flux:card>
    @endif
</section>
