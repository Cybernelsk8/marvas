<?php

namespace App\Livewire\Admin\Consultation;

use App\Models\Consulta;
use App\Services\ConsultationService;
use App\Support\AcupunctureCatalog;
use App\Support\AnamnesisCatalog;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Formulario y operaciones para documentar, cerrar o reabrir una consulta clínica. */
#[Title('Consulta clínica')]
class Show extends Component
{
    public Consulta $consultation;

    public array $form = [
        'motivo_consulta' => '',
        'historia_padecimiento' => '',
        'intensidad_dolor' => '',
        'zona_dolor_principal' => '',
        'antecedentes_estados' => [],
        'revision_sistemas' => [],
        'presion_sistolica' => '',
        'presion_diastolica' => '',
        'frecuencia_cardiaca' => '',
        'frecuencia_respiratoria' => '',
        'temperatura' => '',
        'glicemia' => '',
        'peso_kg' => '',
        'talla_cm' => '',
        'datos_formulario' => [],
    ];

    public array $assessmentForms = [];

    public array $mbiForm = [
        'integracion_clinica' => '',
        'sistemas_comprometidos' => '',
        'hallazgos_principales' => '',
        'objetivo_terapeutico' => '',
        'observaciones_generales' => '',
        'especialidad_principal' => '',
        'especialidades_complementarias' => '',
        'modelo_6plus_seleccion' => '',
    ];

    public array $closure = [
        'motivo_consulta' => '',
        'observaciones_profesional' => '',
    ];

    public ?string $activeAnamnesis = null;

    // Carga la consulta, sus relaciones clínicas y los valores existentes en los formularios; Livewire la invoca al abrir la ficha.
    //   Uso:
    //     <livewire:admin.consultation.show :consultation="$consultation" />
    //   Resultado: La consulta queda lista para editarse según permisos y estado.
    public function mount(Consulta $consultation): void
    {
        abort_unless(auth()->user()?->canAny([
            'consultations.edit',
            'consultations.close',
            'consultations.reopen',
        ]), 403);

        $this->consultation = $consultation->load([
            'expediente.paciente',
            'especialidad',
            'profesional',
            'sucursal',
            'anamnesisGeneral.homeopatica',
            'anamnesisGeneral.quiropraxia',
            'anamnesisGeneral.acupuntura',
            'evaluacionIntegral',
        ]);

        if ($this->consultation->evaluacionIntegral) {
            foreach ($this->mbiForm as $field => $default) {
                $value = $this->consultation->evaluacionIntegral->{$field};
                $this->mbiForm[$field] = is_array($value)
                    ? implode(PHP_EOL, $value)
                    : (string) ($value ?? $default);
            }
        }

        $anamnesis = $this->consultation->anamnesisGeneral;
        $this->form['datos_formulario'] = $this->integralFormDefaults();
        $this->form['antecedentes_estados'] = AnamnesisCatalog::merge(
            AnamnesisCatalog::antecedentes(),
            'no_evaluado',
            $anamnesis?->antecedentes_estados,
        );
        $this->form['revision_sistemas'] = AnamnesisCatalog::merge(
            AnamnesisCatalog::sistemas(),
            'no_evaluado',
            $anamnesis?->revision_sistemas,
        );

        if ($anamnesis) {
            foreach (array_diff(array_keys($this->form), [
                'datos_formulario',
                'antecedentes_estados',
                'revision_sistemas',
            ]) as $field) {
                $value = $anamnesis->{$field};
                $this->form[$field] = is_array($value)
                    ? implode(PHP_EOL, $value)
                    : (string) ($value ?? '');
            }

            $this->closure = [
                'motivo_consulta' => (string) ($anamnesis->motivo_consulta ?? ''),
                'observaciones_profesional' => (string) ($anamnesis->observaciones_profesional ?? ''),
            ];

            foreach ($this->form['datos_formulario'] as $section => $fields) {
                foreach ($fields as $field => $default) {
                    $this->form['datos_formulario'][$section][$field] = (string) (
                        $anamnesis->datos_formulario[$section][$field] ?? $default
                    );
                }
            }
        }

        $this->assessmentForms = $this->assessmentFormDefaults();
        $storedComplementaryData = $anamnesis?->datos_formulario ?? [];
        foreach ([
            'homeopatia' => $anamnesis?->homeopatica,
            'quiropraxia' => $anamnesis?->quiropraxia,
            'acupuntura' => $anamnesis?->acupuntura,
        ] as $formKey => $record) {
            foreach ($this->assessmentForms[$formKey] as $field => $default) {
                if ($field === 'datos_complementarios') {
                    foreach ($default as $complementaryField => $complementaryDefault) {
                        $this->assessmentForms[$formKey][$field][$complementaryField] = (string) (
                            $storedComplementaryData[$formKey][$complementaryField] ?? $complementaryDefault
                        );
                    }

                    continue;
                }

                $value = $record?->{$field};
                if ($formKey === 'acupuntura' && AcupunctureCatalog::isStructured($field)) {
                    $this->assessmentForms[$formKey][$field] = AcupunctureCatalog::merge($field, $value);

                    continue;
                }

                if (isset($this->structuredBlocks($formKey)[$field])) {
                    $block = $this->structuredBlocks($formKey)[$field];
                    $this->assessmentForms[$formKey][$field] = AnamnesisCatalog::merge(
                        $block['catalog'],
                        $block['default'],
                        $value,
                        $block['extra'],
                    );

                    continue;
                }

                $this->assessmentForms[$formKey][$field] = is_array($value)
                    ? implode(PHP_EOL, $value)
                    : ($value ?? $default);
            }
        }
    }

    // Abre la sección clínica solicitada, limpia errores previos y solo admite secciones válidas en consultas editables.
    //   Uso:
    //     wire:click="openAnamnesis('general')"
    //   Resultado: Se muestra el formulario de anamnesis general.
    public function openAnamnesis(string $section): void
    {
        $this->authorize('consultations.edit');
        abort_unless(in_array($section, ['general', 'homeopatia', 'quiropraxia', 'acupuntura', 'mbi', 'mapa_dolor'], true), 404);
        abort_unless(in_array($this->consultation->estado, ['abierta', 'en_proceso'], true), 403);

        $this->activeAnamnesis = $section;
        $this->resetValidation();
    }

    // Recibe el EVA máximo del mapa de dolor y rellena la intensidad y la zona principal de la anamnesis integral.
    //   Uso:
    //     El componente PainMap emite: $this->dispatch('pain-map-saved', eva: 7, zona: 'Lumbar')
    //   Resultado: form.intensidad_dolor y form.zona_dolor_principal quedan pre-llenados; se guardan al guardar la anamnesis.
    #[On('pain-map-saved')]
    public function applyPainMapResult(?int $eva = null, ?string $zona = null): void
    {
        $this->consultation->unsetRelation('mapaCorporal');

        if ($eva === null) {
            return;
        }

        $this->form['intensidad_dolor'] = (string) $eva;
        if (filled($zona)) {
            $this->form['zona_dolor_principal'] = $zona;
        }
    }

    // Cierra el formulario de anamnesis activo y descarta errores de validación para volver al índice de secciones.
    //   Uso:
    //     wire:click="backToAnamneses"
    //   Resultado: La ficha vuelve a mostrar las secciones clínicas.
    public function backToAnamneses(): void
    {
        $this->activeAnamnesis = null;
        $this->resetValidation();
    }

    // Valida y persiste la anamnesis general mediante el servicio, actualiza la relación cargada y cierra la sección.
    //   Uso:
    //     wire:click="saveGeneralAnamnesis"
    //   Resultado: La anamnesis queda guardada y aparece una notificación de éxito.
    public function saveGeneralAnamnesis(ConsultationService $consultationService): void
    {
        $this->authorize('consultations.edit');

        $anamnesis = $consultationService->saveGeneralAnamnesis(
            $this->consultation->id,
            $this->validatedAnamnesis(),
        );
        $this->consultation->setRelation('anamnesisGeneral', $anamnesis);
        $this->activeAnamnesis = null;

        Flux::toast(text: 'Anamnesis general guardada correctamente.', variant: 'success');
    }

    // Guarda en conjunto la anamnesis general y las evaluaciones homeopática y quiropráctica, separando sus datos complementarios.
    //   Uso:
    //     wire:click="saveIntegralAnamnesis"
    //   Resultado: Las tres partes quedan persistidas y la ficha muestra confirmación.
    public function saveIntegralAnamnesis(ConsultationService $consultationService): void
    {
        $this->authorize('consultations.edit');

        $homeopatia = $this->validatedAssessmentForm('homeopatia');
        $quiropraxia = $this->validatedAssessmentForm('quiropraxia');
        $additionalData = [
            'homeopatia' => $homeopatia['datos_complementarios'],
            'quiropraxia' => $quiropraxia['datos_complementarios'],
        ];
        unset($homeopatia['datos_complementarios'], $quiropraxia['datos_complementarios']);

        $anamnesis = $consultationService->saveIntegralAnamnesis(
            $this->consultation->id,
            $this->validatedAnamnesis(),
            [
                'homeopatia' => $homeopatia,
                'quiropraxia' => $quiropraxia,
            ],
            $additionalData,
        );
        $this->consultation->setRelation('anamnesisGeneral', $anamnesis->load([
            'homeopatica',
            'quiropraxia',
            'acupuntura',
        ]));
        $this->activeAnamnesis = null;

        Flux::toast(text: 'Anamnesis integral guardada correctamente.', variant: 'success');
    }

    // Valida y guarda una evaluación individual permitida (homeopatía, quiropraxia o acupuntura) junto con sus datos complementarios.
    //   Uso:
    //     wire:click="saveAssessment('acupuntura')"
    //   Resultado: La evaluación seleccionada se guarda y se confirma con una notificación.
    public function saveAssessment(string $formKey, ConsultationService $consultationService): void
    {
        $this->authorize('consultations.edit');
        abort_unless(in_array($formKey, ['homeopatia', 'quiropraxia', 'acupuntura'], true), 404);

        $formData = $this->validatedAssessmentForm($formKey);
        $complementaryData = $formData['datos_complementarios'];
        unset($formData['datos_complementarios']);

        $anamnesis = $consultationService->saveAssessmentForm(
            $this->consultation->id,
            $formKey,
            $formData,
            $complementaryData,
        );
        $this->consultation->setRelation('anamnesisGeneral', $anamnesis->load([
            'homeopatica',
            'quiropraxia',
            'acupuntura',
        ]));
        $this->activeAnamnesis = null;

        Flux::toast(text: 'Evaluación guardada correctamente.', variant: 'success');
    }

    // Valida la evaluación integral MBI, convierte sus campos de varias líneas a listas y la guarda mediante el servicio.
    //   Uso:
    //     wire:click="saveMbiEvaluation"
    //   Resultado: La evaluación MBI queda actualizada en la consulta.
    public function saveMbiEvaluation(ConsultationService $consultationService): void
    {
        $this->authorize('consultations.edit');

        $data = $this->validate([
            'mbiForm.integracion_clinica' => ['nullable', 'string', 'max:10000'],
            'mbiForm.sistemas_comprometidos' => ['nullable', 'string', 'max:5000'],
            'mbiForm.hallazgos_principales' => ['nullable', 'string', 'max:10000'],
            'mbiForm.objetivo_terapeutico' => ['nullable', 'string', 'max:10000'],
            'mbiForm.observaciones_generales' => ['nullable', 'string', 'max:10000'],
            'mbiForm.especialidad_principal' => ['nullable', 'string', 'max:100'],
            'mbiForm.especialidades_complementarias' => ['nullable', 'string', 'max:5000'],
            'mbiForm.modelo_6plus_seleccion' => ['nullable', 'string', 'max:5000'],
        ])['mbiForm'];

        foreach (['sistemas_comprometidos', 'especialidades_complementarias', 'modelo_6plus_seleccion'] as $field) {
            $data[$field] = $this->linesToArray($data[$field] ?? null);
        }

        foreach ($data as $field => $value) {
            if (! in_array($field, ['sistemas_comprometidos', 'especialidades_complementarias', 'modelo_6plus_seleccion'], true)) {
                $data[$field] = $value === '' ? null : $value;
            }
        }

        $evaluation = $consultationService->saveMbiEvaluation($this->consultation->id, $data);
        $this->consultation->setRelation('evaluacionIntegral', $evaluation);
        $this->activeAnamnesis = null;

        Flux::toast(text: 'Evaluación integral MBI guardada correctamente.', variant: 'success');
    }

    // Valida el motivo y las observaciones de cierre, cierra la consulta y refresca sus relaciones visibles.
    //   Uso:
    //     wire:click="closeConsultation"
    //   Resultado: La consulta queda cerrada y se muestra la confirmación.
    public function closeConsultation(ConsultationService $consultationService): void
    {
        $this->authorize('consultations.close');

        $this->consultation = $consultationService->closeConsultation(
            $this->consultation->id,
            $this->validate([
                'closure.motivo_consulta' => ['required', 'string', 'max:10000'],
                'closure.observaciones_profesional' => ['required', 'string', 'max:10000'],
            ])['closure'],
        )->load([
            'expediente.paciente',
            'especialidad',
            'profesional',
            'sucursal',
            'anamnesisGeneral',
        ]);

        Flux::modal('consultation-close-confirm')->close();
        Flux::toast(text: 'Consulta cerrada correctamente.', variant: 'success');
    }

    // Reabre la consulta mediante el servicio y recarga sus relaciones para reflejar el estado actualizado.
    //   Uso:
    //     wire:click="reopenConsultation"
    //   Resultado: La consulta vuelve al estado en proceso y se muestra la confirmación.
    public function reopenConsultation(ConsultationService $consultationService): void
    {
        $this->authorize('consultations.reopen');

        $this->consultation = $consultationService->reopenConsultation($this->consultation->id)->load([
            'expediente.paciente',
            'especialidad',
            'profesional',
            'sucursal',
            'anamnesisGeneral',
        ]);

        Flux::modal('consultation-reopen-confirm')->close();
        Flux::toast(text: 'Consulta reabierta en proceso.', variant: 'success');
    }

    /**
     * @return View
     */
    // Construye la vista de la consulta con permisos, estado clínico y definiciones de secciones para sus formularios.
    //   Uso:
    //     Livewire ejecuta render() al mostrar o actualizar el componente.
    //   Resultado: Se presenta la ficha con acciones y secciones disponibles según el usuario.
    public function render()
    {
        $user = auth()->user();

        return view('livewire.admin.consultation.show', [
            'editable' => in_array($this->consultation->estado, ['abierta', 'en_proceso'], true)
                && $user?->can('consultations.edit'),
            'canClose' => $user?->can('consultations.close')
                && in_array($this->consultation->estado, ['abierta', 'en_proceso'], true),
            'canReopen' => $user?->can('consultations.reopen')
                && $this->consultation->estado === 'cerrada',
            'anamnesis' => $this->consultation->anamnesisGeneral,
            'mbiEvaluation' => $this->consultation->evaluacionIntegral,
            'anamnesisSections' => $this->anamnesisSections(),
            'integralFormSections' => $this->integralFormSections(),
            'assessmentComplementarySections' => $this->assessmentComplementarySections(),
        ]);
    }

    // Define etiquetas y campos de las secciones del formulario integral para reutilizarlos en la interfaz y validación.
    //   Uso:
    //     $sections = $this->integralFormSections();
    //   Resultado: Devuelve un mapa de secciones con su etiqueta y campos.
    private function integralFormSections(): array
    {
        return [
            'consulta_actual' => [
                'label' => '02 Consulta actual',
                'fields' => [
                    'inicio' => 'Inicio / fecha o tiempo de evolución',
                    'forma_inicio' => 'Forma de inicio',
                    'frecuencia' => 'Frecuencia',
                    'evolucion' => 'Evolución',
                    'momento_dolor' => 'Momento del día en que se presenta',
                    'hormigueo_adormecimiento' => 'Hormigueo o adormecimiento',
                    'debilidad_extremidad_lado' => 'Debilidad: extremidad y lado',
                    'irradiacion' => 'Irradiación y región',
                    'localizacion_caracteristicas' => 'Localización, tipo, horario, duración y patrón',
                    'agravantes_alivio_tratamientos' => 'Agravantes, alivio, tratamientos previos y respuesta',
                ],
            ],
            'antecedentes_habitos' => [
                'label' => '03 Antecedentes familiares y hábitos',
                'fields' => [
                    'antecedentes_familiares' => 'Antecedentes familiares: cáncer, diabetes, hipertensión, artritis, artrosis, hernias, Alzheimer, osteoporosis; parentesco y tratamiento',
                    'tabaco' => 'Tabaco: cantidad y frecuencia',
                    'alcohol' => 'Alcohol: cantidad y frecuencia',
                    'cafe' => 'Café: tazas por día',
                    'otras_sustancias' => 'Otras sustancias: tipo y frecuencia',
                    'dieta_apetito' => 'Dieta y apetito: patrón y cambios',
                    'hidratacion' => 'Hidratación',
                    'actividad_fisica' => 'Actividad física: tipo y veces por semana',
                    'sueno' => 'Sueño: horas y calidad',
                    'estres' => 'Estrés: nivel y factores asociados',
                    'condiciones_laborales' => 'Condiciones laborales y ergonomía',
                ],
            ],
            'examen_general' => [
                'label' => '04 Exámenes complementarios',
                'fields' => [
                    'examenes_complementarios' => 'Exámenes complementarios aportados y resultados relevantes',
                ],
            ],
            'resultado_evaluador' => [
                'label' => '07 Resultado del evaluador: impresión clínica y plan',
                'fields' => [
                    'resumen_clinico' => 'Resumen de síntomas, hallazgos objetivos y valoración diferenciada',
                    'diagnostico_diferenciales' => 'Diagnóstico documentado, hipótesis clínica y diagnósticos diferenciales',
                    'objetivos_intervenciones_precauciones' => 'Objetivos, intervenciones previstas, precauciones, consentimiento y referencia',
                    'plan_seguimiento' => 'Plan de seguimiento, reevaluación y evolución',
                ],
            ],
        ];
    }

    // Describe los campos complementarios de homeopatía, quiropraxia y acupuntura que se presentan en la ficha clínica.
    //   Uso:
    //     $sections = $this->assessmentComplementarySections();
    //   Resultado: Devuelve los títulos y campos complementarios de cada evaluación.
    private function assessmentComplementarySections(): array
    {
        return [
            'homeopatia' => [
                'label' => '05 Complemento homeopático',
                'fields' => [
                    'patrones_sueno_suenos' => 'Patrones de sueño y sueños',
                    'sensibilidad_termica' => 'Sensibilidad térmica',
                    'sed_apetito' => 'Sed y apetito',
                    'preferencias_aversiones' => 'Preferencias y aversiones',
                ],
            ],
            'acupuntura' => [
                'label' => 'Complemento de acupuntura',
                'fields' => [
                    'patrones_inusuales_lengua' => 'Patrones inusuales de la lengua',
                ],
            ],
        ];
    }

    // Genera valores iniciales vacíos para cada campo declarado en las secciones del formulario integral.
    //   Uso:
    //     $defaults = $this->integralFormDefaults();
    //   Resultado: Cada sección contiene sus campos listos para capturar texto.
    private function integralFormDefaults(): array
    {
        $defaults = [];

        foreach ($this->integralFormSections() as $section => $definition) {
            $defaults[$section] = array_fill_keys(array_keys($definition['fields']), '');
        }

        return $defaults;
    }

    // Crea valores vacíos para los campos complementarios de la evaluación identificada por su clave.
    //   Uso:
    //     $defaults = $this->assessmentComplementaryDefaults('homeopatia');
    //   Resultado: Devuelve un arreglo con los campos complementarios de homeopatía vacíos.
    private function assessmentComplementaryDefaults(string $formKey): array
    {
        $fields = $this->assessmentComplementarySections()[$formKey]['fields'] ?? [];

        return array_fill_keys(array_keys($fields), '');
    }

    // Prepara las tarjetas de anamnesis para la vista, indicando descripción, icono y si cada registro ya fue guardado.
    //   Uso:
    //     'anamnesisSections' => $this->anamnesisSections()
    //   Resultado: La interfaz recibe las secciones y su estado de captura.
    private function anamnesisSections(): array
    {
        $anamnesis = $this->consultation->anamnesisGeneral;

        return [
            'general' => [
                'label' => 'Anamnesis integral',
                'description' => 'Anamnesis general, Evaluación homeopática y Evaluación quiropráctica/neuromusculoesquelética.',
                'icon' => 'clipboard-document-list',
                'saved' => filled($anamnesis?->motivo_consulta)
                    || $anamnesis?->homeopatica !== null
                    || $anamnesis?->quiropraxia !== null,
            ],
            'mbi' => [
                'label' => 'Evaluación integral MBI',
                'description' => 'Integración clínica, sistemas comprometidos y orientación terapéutica.',
                'icon' => 'clipboard-document-check',
                'saved' => $this->consultation->evaluacionIntegral !== null,
            ],
            'homeopatia' => [
                'label' => 'Evaluación homeopática',
                'description' => 'Síntomas locales, generales, mentales y biopatográficos.',
                'icon' => 'sparkles',
                'saved' => $anamnesis?->homeopatica !== null,
            ],
            'quiropraxia' => [
                'label' => 'Evaluación quiropráctica',
                'description' => 'Antecedentes y evaluación funcional musculoesquelética.',
                'icon' => 'user',
                'saved' => $anamnesis?->quiropraxia !== null,
            ],
            'acupuntura' => [
                'label' => 'Evaluación de acupuntura',
                'description' => 'Evaluación energética, pulsos, lengua y tratamiento.',
                'icon' => 'adjustments-horizontal',
                'saved' => $anamnesis?->acupuntura !== null,
            ],
            'mapa_dolor' => [
                'label' => 'Mapa de dolor',
                'description' => 'Regiones con dolor, EVA por zona y evolución entre sesiones.',
                'icon' => 'map-pin',
                'saved' => ($this->consultation->mapaCorporal?->estado ?? 'no_evaluado') !== 'no_evaluado',
            ],
        ];
    }

    // Valida los campos generales e integrales de la anamnesis y normaliza listas y valores vacíos antes de persistirlos.
    //   Uso:
    //     $data = $this->validatedAnamnesis();
    //   Resultado: Devuelve los datos validados con campos opcionales normalizados.
    private function validatedAnamnesis(): array
    {
        $rules = [
            'form.motivo_consulta' => ['required', 'string', 'max:10000'],
            'form.historia_padecimiento' => ['nullable', 'string', 'max:10000'],
            'form.intensidad_dolor' => ['nullable', 'integer', 'min:0', 'max:10'],
            'form.zona_dolor_principal' => ['nullable', 'string', 'max:120'],
            'form.presion_sistolica' => ['nullable', 'integer', 'min:0', 'max:400', 'required_with:form.presion_diastolica'],
            'form.presion_diastolica' => [
                'nullable', 'integer', 'min:0', 'max:300', 'required_with:form.presion_sistolica',
                'lte:form.presion_sistolica',
            ],
            'form.frecuencia_cardiaca' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'form.frecuencia_respiratoria' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'form.temperatura' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'form.glicemia' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'form.peso_kg' => ['nullable', 'numeric', 'gt:0', 'max:999.99'],
            'form.talla_cm' => ['nullable', 'numeric', 'gt:0', 'max:999.99'],
            ...AnamnesisCatalog::rules(
                'form.antecedentes_estados',
                AnamnesisCatalog::antecedentes(),
                array_keys(AnamnesisCatalog::ESTADOS['tri']),
            ),
            ...AnamnesisCatalog::rules(
                'form.revision_sistemas',
                AnamnesisCatalog::sistemas(),
                array_keys(AnamnesisCatalog::ESTADOS['sintomas']),
            ),
        ];
        foreach ($this->integralFormSections() as $section => $definition) {
            foreach (array_keys($definition['fields']) as $field) {
                $rules["form.datos_formulario.{$section}.{$field}"] = ['nullable', 'string', 'max:10000'];
            }
        }

        $validated = $this->validate($rules)['form'];

        foreach (['antecedentes_estados', 'revision_sistemas'] as $field) {
            $validated[$field] = AnamnesisCatalog::clean($validated[$field]);
        }

        foreach ($validated as $field => $value) {
            if (! is_array($value)) {
                $validated[$field] = $value === '' ? null : $value;
            }
        }

        foreach ($validated['datos_formulario'] as $section => $fields) {
            foreach ($fields as $field => $value) {
                $validated['datos_formulario'][$section][$field] = $value === '' ? null : $value;
            }
        }

        return $validated;
    }

    // Valida los datos de la evaluación indicada y convierte listas, booleanos y valores vacíos a sus tipos de persistencia.
    //   Uso:
    //     $data = $this->validatedAssessmentForm('quiropraxia');
    //   Resultado: Devuelve los campos de quiropraxia validados y normalizados.
    private function validatedAssessmentForm(string $formKey): array
    {
        $formDefaults = $this->assessmentFormDefaults()[$formKey] ?? null;
        abort_unless(is_array($formDefaults), 404);

        $rules = [];
        foreach ($formDefaults as $field => $default) {
            if ($field === 'datos_complementarios') {
                foreach ($default as $complementaryField => $complementaryValue) {
                    $rules["assessmentForms.{$formKey}.datos_complementarios.{$complementaryField}"] = [
                        'nullable',
                        'string',
                        'max:10000',
                    ];
                }

                continue;
            }

            if (isset($this->structuredBlocks($formKey)[$field])) {
                $block = $this->structuredBlocks($formKey)[$field];
                $rules += AnamnesisCatalog::rules(
                    "assessmentForms.{$formKey}.{$field}",
                    $block['catalog'],
                    array_keys($block['estados']),
                    $block['extraRules'],
                    $block['default'] === '',
                );

                continue;
            }

            if ($formKey === 'acupuntura' && AcupunctureCatalog::isStructured($field)) {
                $rules += AcupunctureCatalog::rules("assessmentForms.{$formKey}", $field);

                continue;
            }

            $rules["assessmentForms.{$formKey}.{$field}"] = match (true) {
                is_bool($default) => ['boolean'],
                $field === 'humedad' => ['nullable', 'in:seca,humeda,pegajosa'],
                $field === 'decision_manipulacion' => ['nullable', 'in:indicada,diferida,contraindicada'],
                $field === 'lengua_movimiento' => [
                    'nullable',
                    'in:'.implode(',', array_keys(AcupunctureCatalog::MOVIMIENTOS_LENGUA)),
                ],
                default => ['nullable', 'string', 'max:10000'],
            };
        }

        $validated = $this->validate($rules)['assessmentForms'][$formKey] ?? [];
        foreach ($formDefaults as $field => $default) {
            if ($field === 'datos_complementarios') {
                $complementaryData = $validated[$field] ?? [];
                foreach ($default as $complementaryField => $complementaryDefault) {
                    $value = $complementaryData[$complementaryField] ?? $complementaryDefault;
                    $complementaryData[$complementaryField] = $value === '' ? null : $value;
                }
                $validated[$field] = $complementaryData;

                continue;
            }

            if (isset($this->structuredBlocks($formKey)[$field])) {
                $block = $this->structuredBlocks($formKey)[$field];
                $validated[$field] = AnamnesisCatalog::isUntouched($validated[$field], $block['default'])
                    ? null
                    : AnamnesisCatalog::clean($validated[$field]);

                continue;
            }

            if ($formKey === 'acupuntura' && AcupunctureCatalog::isStructured($field)) {
                $validated[$field] = AcupunctureCatalog::normalize(
                    $field,
                    $validated[$field] ?? $default,
                );

                continue;
            }

            $value = $validated[$field] ?? $default;
            if (is_bool($default)) {
                $validated[$field] = (bool) $value;
            } elseif ($value === '') {
                $validated[$field] = null;
            }
        }

        // 06D: con una alerta en "Sí" no se puede declarar la manipulación como indicada.
        if ($formKey === 'quiropraxia'
            && ($validated['decision_manipulacion'] ?? null) === 'indicada'
            && collect($validated['seguridad'] ?? [])->contains(fn (array $item): bool => $item['estado'] === 'si')) {
            throw ValidationException::withMessages([
                'assessmentForms.quiropraxia.decision_manipulacion' => 'Hay alertas de seguridad (06D) en «Sí»: la manipulación no puede marcarse como indicada.',
            ]);
        }

        return $validated;
    }

    // Centraliza los valores iniciales y campos de las tres evaluaciones clínicas, incluidos booleanos y listas de acupuntura.
    //   Uso:
    //     $forms = $this->assessmentFormDefaults();
    //   Resultado: Devuelve los formularios de homeopatía, quiropraxia y acupuntura.
    private function assessmentFormDefaults(): array
    {
        return [
            'homeopatia' => [
                ...array_fill_keys([
                    'aspecto_paciente',
                    'sintomas_locales_generales',
                    'sintomas_mentales',
                    'sintomas_biopatograficos',
                    'diagnostico_posible',
                    'tratamiento_indicado',
                    'observaciones',
                ], ''),
                'datos_complementarios' => $this->assessmentComplementaryDefaults('homeopatia'),
            ],
            'quiropraxia' => [
                ...array_map(
                    fn (array $block): array => AnamnesisCatalog::defaults($block['catalog'], $block['default'], $block['extra']),
                    $this->structuredBlocks('quiropraxia'),
                ),
                ...array_fill_keys([
                    'otras_pruebas',
                    'decision_manipulacion',
                    'motivo_decision',
                    'diagnostico',
                    'plan_seguimiento_inicial',
                    'observaciones',
                ], ''),
                'datos_complementarios' => $this->assessmentComplementaryDefaults('quiropraxia'),
            ],
            'acupuntura' => [
                ...array_fill_keys([
                    'diagnostico_energetico',
                    'humedad',
                    'lengua_movimiento',
                    'principio_terapeutico',
                    'observaciones',
                ], ''),
                ...AcupunctureCatalog::defaults(),
                'datos_complementarios' => $this->assessmentComplementaryDefaults('acupuntura'),
            ],
        ];
    }


    // Describe los bloques de selectores de tres estados de una evaluación: catálogo, estados válidos, estado inicial y extras por ítem.
    //   Uso:
    //     $blocks = $this->structuredBlocks('quiropraxia');
    //     $blocks['seguridad']['default']; // 'no_evaluado'
    //   Resultado: Devuelve un arreglo vacío para evaluaciones sin bloques estructurados.
    private function structuredBlocks(string $formKey): array
    {
        if ($formKey !== 'quiropraxia') {
            return [];
        }

        return [
            'sintomas' => [
                'catalog' => AnamnesisCatalog::sintomas(),
                'estados' => AnamnesisCatalog::ESTADOS['sintomas'],
                'default' => 'no_evaluado',
                'extra' => [],
                'extraRules' => [],
            ],
            'postura_movilidad' => [
                'catalog' => AnamnesisCatalog::posturaMovilidad(),
                'estados' => AnamnesisCatalog::ESTADOS['exploracion_a'],
                'default' => 'no_evaluado',
                'extra' => [],
                'extraRules' => [],
            ],
            'pruebas_neuro_ortopedicas' => [
                'catalog' => AnamnesisCatalog::pruebas(),
                'estados' => AnamnesisCatalog::ESTADOS['exploracion_b'],
                'default' => 'no_realizado',
                'extra' => ['lado' => '', 'fuerza' => ''],
                'extraRules' => [
                    'lado' => ['nullable', 'in:'.implode(',', array_keys(AnamnesisCatalog::LATERALIDADES))],
                    'fuerza' => ['nullable', 'integer', 'min:0', 'max:5'],
                ],
            ],
            'indice_funcional' => [
                'catalog' => AnamnesisCatalog::indiceFuncional(),
                'estados' => AnamnesisCatalog::ESTADOS['indice'],
                'default' => '',
                'extra' => [],
                'extraRules' => [],
            ],
            'seguridad' => [
                'catalog' => AnamnesisCatalog::seguridad(),
                'estados' => AnamnesisCatalog::ESTADOS['tri'],
                'default' => 'no_evaluado',
                'extra' => [],
                'extraRules' => [],
            ],
        ];
    }

    // Divide un texto por saltos de línea, recorta espacios y elimina entradas vacías para guardar una lista limpia.
    //   Uso:
    //     $items = $this->linesToArray("uno\ndos");
    //   Resultado: Devuelve ['uno', 'dos']; si el texto está vacío, devuelve un arreglo vacío.
    private function linesToArray(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return collect(preg_split('/\R/', $value) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
