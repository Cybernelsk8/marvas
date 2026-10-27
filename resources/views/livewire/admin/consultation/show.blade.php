<section class="mx-auto w-full max-w-6xl space-y-6 p-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Consulta {{ $consultation->numero_consulta }}</flux:heading>
            <flux:text class="mt-2">
                {{ $consultation->expediente->paciente->nombre_completo }}
                · {{ $consultation->fecha_hora->format('d/m/Y H:i') }}
                · {{ $consultation->especialidad->nombre }}
            </flux:text>
        </div>
        <flux:button
            :href="route('admin.patients.show', $consultation->expediente->paciente)"
            variant="ghost"
            icon="arrow-left"
            wire:navigate
        >
            Volver a la ficha
        </flux:button>
    </header>

    <flux:card class="space-y-3">
        <flux:heading size="lg">Datos de la consulta</flux:heading>
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm text-zinc-500">Paciente</dt>
                <dd>{{ $consultation->expediente->paciente->nombre_completo }}</dd>
            </div>
            <div>
                <dt class="text-sm text-zinc-500">Profesional</dt>
                <dd>{{ $consultation->profesional->nombre_completo }}</dd>
            </div>
            <div>
                <dt class="text-sm text-zinc-500">Sede</dt>
                <dd>{{ $consultation->sucursal->nombre }}</dd>
            </div>
            <div>
                <dt class="text-sm text-zinc-500">Estado</dt>
                <dd>{{ str($consultation->estado)->replace('_', ' ')->ucfirst() }}</dd>
            </div>
        </dl>
    </flux:card>

    @if (!$editable)
        <flux:callout
            variant="warning"
            icon="lock-closed"
        >
            <flux:callout.heading>Consulta no editable</flux:callout.heading>
            <flux:callout.text>
                @if ($consultation->estado === 'cerrada')
                    La consulta está cerrada. Reábrala para continuar editando sus formularios.
                @else
                    No tiene permiso para editar los formularios clínicos.
                @endif
            </flux:callout.text>
        </flux:callout>
    @elseif ($activeAnamnesis === null)
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Formularios de la consulta</flux:heading>
                <flux:text class="mt-1">
                    Cada formulario se abre y se guarda de manera independiente.
                </flux:text>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($anamnesisSections as $key => $section)
                    @if (in_array($key, ['general', 'acupuntura', 'mbi', 'mapa_dolor'], true))
                        <flux:card
                            wire:key="anamnesis-section-{{ $key }}"
                            class="flex flex-col gap-4"
                        >
                            <flux:card.header>
                                <flux:card.heading>
                                    <div class="flex items-center justify-between gap-3">
                                        <span>
                                            {{ $section['label'] }}
                                        </span>
                                        <span>
                                            <flux:badge :color="$section['saved'] ? 'green' : 'zinc'">
                                                {{ $section['saved'] ? 'Guardado' : 'Pendiente' }}
                                            </flux:badge>
                                        </span>
                                    </div>
                                </flux:card.heading>

                            </flux:card.header>
                            <flux:card.body class="space-y-6">
                                <flux:card.subheading>{{ $section['description'] }}</flux:card.subheading>
                            </flux:card.body>
                            <flux:card.footer>
                                <flux:card.actions>
                                    <flux:button
                                        variant="primary"
                                        :icon="$section['icon']"
                                        wire:click="openAnamnesis('{{ $key }}')"
                                    >
                                        {{ $section['saved'] ? 'Abrir formulario' : 'Capturar' }}
                                    </flux:button>
                                </flux:card.actions>
                            </flux:card.footer>
                        </flux:card>
                    @endif
                @endforeach
            </div>
        </div>
    @elseif ($activeAnamnesis === 'general')
        <form
            wire:submit="saveIntegralAnamnesis"
            class="space-y-6"
        >
            <div class="flex items-center justify-between gap-3">
                <flux:heading size="lg">Anamnesis integral</flux:heading>
                <flux:button
                    type="button"
                    variant="ghost"
                    wire:click="backToAnamneses"
                >Volver a formularios</flux:button>
            </div>
            <flux:card class="space-y-3">
                <flux:heading size="lg">01 Identificación</flux:heading>
                <dl class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-zinc-500">Expediente</dt>
                        <dd>{{ $consultation->expediente->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Paciente</dt>
                        <dd>{{ $consultation->expediente->paciente->nombre_completo }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Fecha de consulta</dt>
                        <dd>{{ $consultation->fecha_hora->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Nacimiento / edad</dt>
                        <dd>{{ $consultation->expediente->paciente->fecha_nacimiento?->format('d/m/Y') ?: 'No registrada' }}{{ $consultation->expediente->paciente->fecha_nacimiento ? ' / ' . $consultation->expediente->paciente->fecha_nacimiento->age . ' años' : '' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Sexo / estado civil</dt>
                        <dd>{{ $consultation->expediente->paciente->sexo ?: 'No registrado' }} /
                            {{ $consultation->expediente->paciente->estado_civil ?: 'No registrado' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">DPI</dt>
                        <dd>{{ $consultation->expediente->paciente->numero_dpi ?: 'No registrado' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Dirección</dt>
                        <dd>{{ $consultation->expediente->paciente->direccion ?: 'No registrada' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Teléfono / emergencia</dt>
                        <dd>{{ $consultation->expediente->paciente->telefono ?: 'No registrado' }} /
                            {{ $consultation->expediente->paciente->telefono_emergencia ?: 'No registrado' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Ocupación / religión</dt>
                        <dd>{{ $consultation->expediente->paciente->ocupacion ?: 'No registrada' }} /
                            {{ $consultation->expediente->paciente->religion ?: 'No registrada' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Terapeuta responsable</dt>
                        <dd>{{ $consultation->profesional->nombre_completo }}</dd>
                    </div>
                </dl>
            </flux:card>
            <flux:card class="space-y-5">
                <flux:textarea
                    label="Motivo de consulta *"
                    wire:model="form.motivo_consulta"
                    rows="3"
                    required
                />
                @error('form.motivo_consulta')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
                <flux:textarea
                    label="Historia del padecimiento"
                    wire:model="form.historia_padecimiento"
                    rows="4"
                />
                @error('form.historia_padecimiento')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
                <div class="grid gap-4 md:grid-cols-3">
                    <flux:input
                        label="Intensidad del dolor (0–10)"
                        type="number"
                        min="0"
                        max="10"
                        wire:model="form.intensidad_dolor"
                    />
                    <flux:input
                        label="Zona principal del dolor"
                        wire:model="form.zona_dolor_principal"
                        class="md:col-span-2"
                    />
                </div>
                @error('form.intensidad_dolor')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
                <flux:heading size="sm">03 Antecedentes personales</flux:heading>
                @include('livewire.admin.consultation.partials.state-block', [
                    'model' => 'form.antecedentes_estados',
                    'catalog' => \App\Support\AnamnesisCatalog::antecedentes(),
                    'estados' => \App\Support\AnamnesisCatalog::ESTADOS['tri'],
                ])
                <flux:heading size="sm">04 Revisión por sistemas</flux:heading>
                @include('livewire.admin.consultation.partials.state-block', [
                    'model' => 'form.revision_sistemas',
                    'catalog' => \App\Support\AnamnesisCatalog::sistemas(),
                    'estados' => \App\Support\AnamnesisCatalog::ESTADOS['sintomas'],
                ])
            </flux:card>
            <flux:card class="space-y-5">
                <flux:heading size="lg">Signos vitales y medidas</flux:heading>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <flux:input
                        label="Presión sistólica (mmHg)"
                        type="number"
                        min="0"
                        wire:model="form.presion_sistolica"
                    />
                    <flux:input
                        label="Presión diastólica (mmHg)"
                        type="number"
                        min="0"
                        wire:model="form.presion_diastolica"
                    />
                    @error('form.presion_sistolica')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    @error('form.presion_diastolica')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    <flux:input
                        label="Frecuencia cardíaca (lpm)"
                        type="number"
                        min="0"
                        wire:model="form.frecuencia_cardiaca"
                    />
                    <flux:input
                        label="Frecuencia respiratoria (rpm)"
                        type="number"
                        min="0"
                        wire:model="form.frecuencia_respiratoria"
                    />
                    <flux:input
                        label="Temperatura (°C)"
                        type="number"
                        step="0.1"
                        min="0"
                        wire:model="form.temperatura"
                    />
                    <flux:input
                        label="Glicemia (mg/dL)"
                        type="number"
                        min="0"
                        wire:model="form.glicemia"
                    />
                    <flux:input
                        label="Peso (kg)"
                        type="number"
                        step="0.01"
                        min="0.01"
                        wire:model="form.peso_kg"
                    />
                    <flux:input
                        label="Talla (cm)"
                        type="number"
                        step="0.01"
                        min="0.01"
                        wire:model="form.talla_cm"
                    />
                    <div>
                        <span class="text-sm text-zinc-500">IMC calculado</span>
                        <p class="mt-2">{{ $anamnesis?->imc ?? 'Se calculará al registrar peso y talla' }}</p>
                    </div>
                </div>
            </flux:card>
            @include('livewire.admin.consultation.assessments.homeopathy')
            @include('livewire.admin.consultation.assessments.chiropractic')
            @foreach ($integralFormSections as $section => $definition)
                <flux:card
                    wire:key="integral-section-{{ $section }}"
                    class="space-y-5"
                >
                    <flux:heading size="lg">{{ $definition['label'] }}</flux:heading>
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($definition['fields'] as $field => $label)
                            <flux:textarea
                                label="{{ $label }}"
                                wire:model="form.datos_formulario.{{ $section }}.{{ $field }}"
                                rows="3"
                                placeholder="Anota hallazgos o escribe «No evaluado» cuando corresponda."
                            />
                        @endforeach
                    </div>
                </flux:card>
            @endforeach
            @error('anamnesis')
                <flux:callout
                    variant="danger"
                    icon="exclamation-triangle"
                >
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                </flux:callout>
            @enderror
            <div class="flex justify-end">
                <flux:button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                >Guardar anamnesis integral</flux:button>
            </div>
        </form>
    @elseif ($activeAnamnesis === 'mbi')
        <form
            wire:submit="saveMbiEvaluation"
            class="space-y-6"
        >
            <div class="flex items-center justify-between gap-3">
                <flux:heading size="lg">Evaluación integral MBI</flux:heading>
                <flux:button
                    type="button"
                    variant="ghost"
                    wire:click="backToAnamneses"
                >Volver a formularios</flux:button>
            </div>
            @include('livewire.admin.consultation.assessments.mbi')
            @error('mbi')
                <flux:callout
                    variant="danger"
                    icon="exclamation-triangle"
                >
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                </flux:callout>
            @enderror
            <div class="flex justify-end">
                <flux:button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                >
                    Guardar evaluación MBI
                </flux:button>
            </div>
        </form>
    @else
        <div class="space-y-6">
            <div class="flex items-center justify-between gap-3">
                <flux:heading size="lg">{{ $anamnesisSections[$activeAnamnesis]['label'] }}</flux:heading>
                <flux:button
                    type="button"
                    variant="ghost"
                    wire:click="backToAnamneses"
                >Volver a formularios</flux:button>
            </div>
            @if ($activeAnamnesis === 'homeopatia')
                <form
                    wire:submit="saveAssessment('homeopatia')"
                    class="space-y-4"
                >
                    @include('livewire.admin.consultation.assessments.homeopathy')
                    @error('assessmentForms.homeopatia')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    <div class="flex justify-end">
                        <flux:button
                            type="submit"
                            variant="primary"
                            wire:loading.attr="disabled"
                        >Guardar evaluación homeopática</flux:button>
                    </div>
                </form>
            @elseif ($activeAnamnesis === 'quiropraxia')
                <form
                    wire:submit="saveAssessment('quiropraxia')"
                    class="space-y-4"
                >
                    @include('livewire.admin.consultation.assessments.chiropractic')
                    @error('assessmentForms.quiropraxia')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    <div class="flex justify-end">
                        <flux:button
                            type="submit"
                            variant="primary"
                            wire:loading.attr="disabled"
                        >Guardar evaluación quiropráctica</flux:button>
                    </div>
                </form>
            @elseif ($activeAnamnesis === 'acupuntura')
                <form
                    wire:submit="saveAssessment('acupuntura')"
                    class="space-y-4"
                >
                    @include('livewire.admin.consultation.assessments.acupuncture')
                    @error('assessmentForms.acupuntura')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    <div class="flex justify-end">
                        <flux:button
                            type="submit"
                            variant="primary"
                            wire:loading.attr="disabled"
                        >Guardar evaluación de acupuntura</flux:button>
                    </div>
                </form>
            @elseif ($activeAnamnesis === 'mapa_dolor')
                @livewire(\App\Livewire\Admin\Consultation\PainMap::class, ['consultationId' => $consultation->id, 'editable' => true], key('pain-map-'.$consultation->id))
            @endif
            @error('anamnesis')
                <flux:callout
                    variant="danger"
                    icon="exclamation-triangle"
                >
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                </flux:callout>
            @enderror
        </div>
    @endif

    @if ($canClose || $canReopen)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Estado de la consulta</flux:heading>
            @error('consultation')
                <flux:callout
                    variant="danger"
                    icon="exclamation-triangle"
                >
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                </flux:callout>
            @enderror
            @if ($canClose)
                <flux:text>
                    El cierre es independiente de los formularios clínicos. Completa el motivo y las observaciones
                    finales en la confirmación.
                </flux:text>
                <div class="flex justify-end">
                    <flux:modal.trigger name="consultation-close-confirm">
                        <flux:button variant="danger">Cerrar consulta</flux:button>
                    </flux:modal.trigger>
                </div>
            @elseif ($canReopen)
                <flux:text>La consulta volverá al estado «En proceso» y podrá editarse nuevamente.</flux:text>
                <div class="flex justify-end">
                    <flux:modal.trigger name="consultation-reopen-confirm">
                        <flux:button variant="primary">Reabrir consulta</flux:button>
                    </flux:modal.trigger>
                </div>
            @endif
        </flux:card>
    @endif

    @if ($canClose)
        <flux:modal name="consultation-close-confirm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">¿Cerrar esta consulta?</flux:heading>
                    <flux:text class="mt-2">
                        El cierre guardará únicamente el motivo y las observaciones finales; los cambios sin guardar de
                        otros formularios no se conservarán.
                    </flux:text>
                </div>
                <flux:card class="space-y-3">
                    <flux:heading size="sm">Estado de los formularios</flux:heading>
                    <ul class="space-y-2">
                        @foreach ($anamnesisSections as $section)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span>{{ $section['label'] }}</span>
                                <flux:badge :color="$section['saved'] ? 'green' : 'zinc'">
                                    {{ $section['saved'] ? 'Guardado' : 'Pendiente' }}
                                </flux:badge>
                            </li>
                        @endforeach
                    </ul>
                </flux:card>
                <form
                    wire:submit="closeConsultation"
                    class="space-y-4"
                >
                    <flux:textarea
                        label="Motivo de consulta *"
                        wire:model="closure.motivo_consulta"
                        rows="3"
                        required
                    />
                    @error('closure.motivo_consulta')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    <flux:textarea
                        label="Observaciones finales del profesional *"
                        wire:model="closure.observaciones_profesional"
                        rows="4"
                        required
                    />
                    @error('closure.observaciones_profesional')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    @error('consultation')
                        <flux:callout
                            variant="danger"
                            icon="exclamation-triangle"
                        >
                            <flux:callout.text>{{ $message }}</flux:callout.text>
                        </flux:callout>
                    @enderror
                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button
                                type="button"
                                variant="ghost"
                            >Cancelar</flux:button>
                        </flux:modal.close>
                        <flux:button
                            type="submit"
                            variant="danger"
                            wire:loading.attr="disabled"
                        >
                            Confirmar cierre
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
    @endif

    @if ($canReopen)
        <flux:modal name="consultation-reopen-confirm">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">¿Reabrir esta consulta?</flux:heading>
                    <flux:text class="mt-2">
                        La consulta cambiará a «En proceso» y se habilitará nuevamente la edición de la anamnesis.
                    </flux:text>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button
                            type="button"
                            variant="ghost"
                        >Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button
                        variant="primary"
                        wire:click="reopenConsultation"
                        wire:loading.attr="disabled"
                    >
                        Confirmar reapertura
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</section>
