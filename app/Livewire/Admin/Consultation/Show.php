<?php

namespace App\Livewire\Admin\Consultation;

use App\Models\Consulta;
use App\Services\ConsultationService;
use Flux\Flux;
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
        'antecedentes_patologicos' => '',
        'antecedentes_quirurgicos' => '',
        'antecedentes_traumaticos' => '',
        'alergias' => '',
        'medicamentos_actuales' => '',
        'habitos' => '',
        'sueno' => '',
        'alimentacion' => '',
        'estado_emocional' => '',
        'presion_arterial' => '',
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

    /**
     * @param Consulta $consultation
     * @return void
     */
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

        if ($anamnesis) {
            foreach (array_diff(array_keys($this->form), ['datos_formulario']) as $field) {
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
                $this->assessmentForms[$formKey][$field] = is_array($value)
                    ? implode(PHP_EOL, $value)
                    : ($value ?? $default);
            }
        }
    }

    /**
     * @param string $section
     * @return void
     */
    // Abre la sección clínica solicitada, limpia errores previos y solo admite secciones válidas en consultas editables.
    //   Uso:
    //     wire:click="openAnamnesis('general')"
    //   Resultado: Se muestra el formulario de anamnesis general.
    public function openAnamnesis(string $section): void
    {
        $this->authorize('consultations.edit');
        abort_unless(in_array($section, ['general', 'homeopatia', 'quiropraxia', 'acupuntura', 'mbi'], true), 404);
        abort_unless(in_array($this->consultation->estado, ['abierta', 'en_proceso'], true), 403);

        $this->activeAnamnesis = $section;
        $this->resetValidation();
    }

    /**
     * @return void
     */
    // Cierra el formulario de anamnesis activo y descarta errores de validación para volver al índice de secciones.
    //   Uso:
    //     wire:click="backToAnamneses"
    //   Resultado: La ficha vuelve a mostrar las secciones clínicas.
    public function backToAnamneses(): void
    {
        $this->activeAnamnesis = null;
        $this->resetValidation();
    }

    /**
     * @param ConsultationService $consultationService
     * @return void
     */
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

    /**
     * @param ConsultationService $consultationService
     * @return void
     */
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

    /**
     * @param string $formKey
     * @param ConsultationService $consultationService
     * @return void
     */
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

    /**
     * @param ConsultationService $consultationService
     * @return void
     */
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

    /**
     * @param ConsultationService $consultationService
     * @return void
     */
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

    /**
     * @param ConsultationService $consultationService
     * @return void
     */
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
     * @return \Illuminate\Contracts\View\View
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

    /**
     * @return array
     */
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
                    'intensidad_zona_momento' => 'Intensidad (0–10), zona y momento',
                    'hormigueo_adormecimiento' => 'Hormigueo o adormecimiento',
                    'debilidad_extremidad_lado' => 'Debilidad: extremidad y lado',
                    'irradiacion' => 'Irradiación y región',
                    'localizacion_caracteristicas' => 'Localización, tipo, horario, duración y patrón',
                    'agravantes_alivio_tratamientos' => 'Agravantes, alivio, tratamientos previos y respuesta',
                ],
            ],
            'antecedentes_habitos' => [
                'label' => '03 Antecedentes y hábitos',
                'fields' => [
                    'enfermedades_cronicas_previas' => 'Enfermedades crónicas y previas',
                    'cirugias_hospitalizaciones' => 'Cirugías y hospitalizaciones: tipo, motivo y fecha',
                    'traumatismos_fracturas' => 'Traumatismos y fracturas: región y fecha',
                    'implantes_protesis' => 'Implantes metálicos o prótesis: tipo y localización',
                    'embarazo' => 'Embarazo posible: semanas y fecha de última menstruación, si aplica',
                    'antecedentes_familiares' => 'Antecedentes familiares: enfermedad, parentesco y tratamiento',
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
            'revision_sistemas' => [
                'label' => '04 Revisión por sistemas y examen general',
                'fields' => [
                    'piel_anexos' => 'Piel y anexos',
                    'cabeza_cuello' => 'Cabeza y cuello',
                    'ojos' => 'Ojos',
                    'oidos_equilibrio' => 'Oídos y equilibrio',
                    'nariz_senos' => 'Nariz y senos',
                    'boca_garganta' => 'Boca y garganta',
                    'mamas' => 'Mamas, si corresponde',
                    'respiratorio' => 'Respiratorio',
                    'cardiovascular' => 'Cardiovascular',
                    'gastrointestinal' => 'Gastrointestinal',
                    'oseo_muscular' => 'Óseo-muscular',
                    'urinario' => 'Urinario',
                    'reproductor_masculino' => 'Reproductor masculino, si corresponde',
                    'reproductor_femenino' => 'Reproductor femenino, si corresponde',
                    'neurologico' => 'Neurológico',
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

    /**
     * @return array
     */
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
            'quiropraxia' => [
                'label' => '06 Evaluación quiropráctica y neuromusculoesquelética',
                'fields' => [
                    'cefalea_recurrente' => 'Cefalea recurrente: localización, frecuencia y asociación cervical',
                    'lentes_contacto' => 'Uso de lentes de contacto: tiempo y motivo',
                    'alteraciones_equilibrio' => 'Alteraciones del equilibrio: vértigo, inestabilidad y caídas',
                    'ortodoncia_protesis_dental' => 'Ortodoncia o prótesis dental: tipo y fecha',
                    'atm_bruxismo' => 'ATM o bruxismo: dolor, chasquido, bloqueo y apertura',
                    'infecciones_garganta' => 'Infecciones de garganta recurrentes: frecuencia y antecedentes',
                    'zumbido_oido' => 'Zumbido de oído: lateralidad e inicio',
                    'paralisis_facial' => 'Parálisis facial previa: lado, fecha y secuelas',
                    'dolor_ciatico' => 'Dolor ciático: lado, trayecto y parestesias',
                    'adormecimiento_corporal' => 'Adormecimiento corporal: zona, distribución y progresión',
                    'problemas_dermatologicos' => 'Problemas dermatológicos: localización',
                    'cervicalgia' => 'Cervicalgia: irradiación, rigidez e intensidad',
                    'dorsalgia' => 'Dorsalgia: localización, respiración y postura',
                    'lumbalgia' => 'Lumbalgia: irradiación, postura y carga',
                    'dolor_sacroiliaco_pelvico' => 'Dolor sacroilíaco o pélvico: lateralidad y marcha',
                    'dolor_hombros_brazos_manos' => 'Dolor de hombros, brazos o manos: lado y movimiento',
                    'dolor_cadera_rodilla_pies' => 'Dolor de cadera, rodilla o pies: lado y marcha',
                    'dolor_nocturno_reposo' => 'Dolor nocturno o en reposo: despertares y progresión',
                    'mapa_dolor' => 'Mapa del dolor: región, lateralidad, irradiación, inicio, intensidad EVA y características',
                    'postura_cabeza' => 'Postura: posición de cabeza',
                    'postura_hombros' => 'Postura: alineación de hombros',
                    'curvaturas_vertebrales' => 'Curvaturas vertebrales: cifosis, lordosis y región',
                    'alineacion_tronco' => 'Alineación del tronco',
                    'alineacion_pelvica' => 'Alineación pélvica',
                    'marcha_apoyo_plantar' => 'Marcha y apoyo plantar',
                    'equilibrio' => 'Equilibrio: prueba realizada y estabilidad',
                    'movilidad_cervical' => 'Movilidad cervical',
                    'movilidad_dorsal' => 'Movilidad dorsal',
                    'movilidad_lumbar' => 'Movilidad lumbar',
                    'movilidad_extremidades' => 'Movilidad de extremidades: articulación, lado y movimiento',
                    'palpacion_muscular' => 'Palpación muscular: zona, sensibilidad y espasmo',
                    'fuerza_muscular' => 'Fuerza muscular segmentaria (0–5): lado, resultado y detalle',
                    'sensibilidad_dermatomas' => 'Sensibilidad por dermatomas: lado, resultado y detalle',
                    'reflejos_osteotendinosos' => 'Reflejos osteotendinosos: lado, resultado y detalle',
                    'prueba_lasegue' => 'Elevación de pierna recta (Lasègue): lado, resultado y detalle',
                    'prueba_slump' => 'Prueba de Slump: lado, resultado y detalle',
                    'prueba_spurling' => 'Spurling o provocación cervical: lado, resultado y detalle',
                    'pruebas_sacroiliacas' => 'Pruebas sacroilíacas: lado, resultado y detalle',
                    'pruebas_hombro_cadera' => 'Pruebas de hombro o cadera: lado, resultado y detalle',
                    'otras_pruebas' => 'Otras pruebas: resultados, interpretación y limitaciones de exploración',
                    'alerta_deficit_neurologico' => 'Alarma: déficit neurológico nuevo o progresivo',
                    'alerta_esfinteres' => 'Alarma: alteración de esfínteres o anestesia en silla de montar',
                    'alerta_trauma' => 'Alarma: trauma importante o sospecha de fractura',
                    'alerta_fiebre_infeccion' => 'Alarma: fiebre o sospecha de infección espinal',
                    'alerta_cancer' => 'Alarma: cáncer conocido con dolor nuevo inexplicado',
                    'alerta_osteoporosis' => 'Alarma: osteoporosis severa o fragilidad ósea',
                    'alerta_anticoagulacion' => 'Alarma: anticoagulación o sangrado relevante',
                    'alerta_vascular_cervical' => 'Alarma: síntomas vasculares cervicales',
                    'alerta_cirugia_inestabilidad' => 'Alarma: cirugía reciente o inestabilidad conocida',
                    'decision_seguridad' => 'Decisión: manipulación indicada, diferida o contraindicada; motivo y referencia',
                ],
            ],
            'acupuntura' => [
                'label' => 'Complemento de acupuntura individual',
                'fields' => [
                    'movimiento_lengua' => 'Movimiento de la lengua: normal, rígida, temblorosa, débil o desviada',
                    'protocolo' => 'Protocolo de acupuntura',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
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

    /**
     * @param string $formKey
     * @return array
     */
    // Crea valores vacíos para los campos complementarios de la evaluación identificada por su clave.
    //   Uso:
    //     $defaults = $this->assessmentComplementaryDefaults('homeopatia');
    //   Resultado: Devuelve un arreglo con los campos complementarios de homeopatía vacíos.
    private function assessmentComplementaryDefaults(string $formKey): array
    {
        $fields = $this->assessmentComplementarySections()[$formKey]['fields'] ?? [];

        return array_fill_keys(array_keys($fields), '');
    }

    /**
     * @return array
     */
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
        ];
    }

    /**
     * @return array
     */
    // Valida los campos generales e integrales de la anamnesis y normaliza listas y valores vacíos antes de persistirlos.
    //   Uso:
    //     $data = $this->validatedAnamnesis();
    //   Resultado: Devuelve los datos validados con campos opcionales normalizados.
    private function validatedAnamnesis(): array
    {
        $rules = [
            'form.motivo_consulta' => ['required', 'string', 'max:10000'],
            'form.historia_padecimiento' => ['nullable', 'string', 'max:10000'],
            'form.antecedentes_patologicos' => ['nullable', 'string', 'max:5000'],
            'form.antecedentes_quirurgicos' => ['nullable', 'string', 'max:5000'],
            'form.antecedentes_traumaticos' => ['nullable', 'string', 'max:5000'],
            'form.alergias' => ['nullable', 'string', 'max:5000'],
            'form.medicamentos_actuales' => ['nullable', 'string', 'max:5000'],
            'form.habitos' => ['nullable', 'string', 'max:5000'],
            'form.sueno' => ['nullable', 'string', 'max:5000'],
            'form.alimentacion' => ['nullable', 'string', 'max:5000'],
            'form.estado_emocional' => ['nullable', 'string', 'max:5000'],
            'form.presion_arterial' => ['nullable', 'string', 'max:20'],
            'form.frecuencia_cardiaca' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'form.frecuencia_respiratoria' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'form.temperatura' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'form.glicemia' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'form.peso_kg' => ['nullable', 'numeric', 'gt:0', 'max:999.99'],
            'form.talla_cm' => ['nullable', 'numeric', 'gt:0', 'max:999.99'],
        ];
        foreach ($this->integralFormSections() as $section => $definition) {
            foreach (array_keys($definition['fields']) as $field) {
                $rules["form.datos_formulario.{$section}.{$field}"] = ['nullable', 'string', 'max:10000'];
            }
        }

        $validated = $this->validate($rules)['form'];

        foreach (['antecedentes_patologicos', 'habitos'] as $field) {
            $validated[$field] = $this->linesToArray($validated[$field] ?? null);
        }

        foreach ($validated as $field => $value) {
            if ($field !== 'antecedentes_patologicos' && $field !== 'habitos') {
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

    /**
     * @param string $formKey
     * @return array
     */
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

            $rules["assessmentForms.{$formKey}.{$field}"] = match (true) {
                is_bool($default) => ['boolean'],
                $field === 'semanas_gestacion' => ['nullable', 'integer', 'min:1', 'max:42'],
                $field === 'humedad' => ['nullable', 'in:seca,humeda,pegajosa'],
                in_array($field, [
                    'calidad_sueno',
                    'desplazamiento',
                    'tiempo_trabajo',
                    'recreacion',
                ], true) => $this->enumRules($field),
                str_starts_with($field, 'pulso_') => ['nullable', 'string', 'max:60'],
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

            $value = $validated[$field] ?? $default;
            if (in_array($field, $this->assessmentArrayFields($formKey), true)) {
                $validated[$field] = $this->linesToArray($value);
            } elseif (is_bool($default)) {
                $validated[$field] = (bool) $value;
            } elseif ($value === '') {
                $validated[$field] = null;
            }
        }

        return $validated;
    }

    /**
     * @return array
     */
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
                ...array_fill_keys([
                    'cirugias',
                    'fracturas',
                    'hospitalizaciones',
                    'detalle_implantes',
                    'zonas_adormecidas',
                    'detalle_atm',
                    'postura',
                    'diagnostico',
                    'plan_seguimiento_inicial',
                    'observaciones',
                ], ''),
                'implantes_metales' => false,
                'embarazo' => false,
                'semanas_gestacion' => '',
                'adormecimientos' => false,
                'ciatica' => false,
                'dolor_cabeza' => false,
                'vertigo' => false,
                'atm' => false,
                'calidad_sueno' => '',
                'desplazamiento' => '',
                'tiempo_trabajo' => '',
                'recreacion' => '',
                'datos_complementarios' => $this->assessmentComplementaryDefaults('quiropraxia'),
            ],
            'acupuntura' => [
                ...array_fill_keys([
                    'diagnostico_energetico',
                    'pulso_cun_der_superficial',
                    'pulso_cun_der_profundo',
                    'pulso_guan_der_superficial',
                    'pulso_guan_der_profundo',
                    'pulso_chi_der_superficial',
                    'pulso_chi_der_profundo',
                    'pulso_cun_izq_superficial',
                    'pulso_cun_izq_profundo',
                    'pulso_guan_izq_superficial',
                    'pulso_guan_izq_profundo',
                    'pulso_chi_izq_superficial',
                    'pulso_chi_izq_profundo',
                    'humedad',
                    'principio_terapeutico',
                    'observaciones',
                ], ''),
                ...array_fill_keys($this->assessmentArrayFields('acupuntura'), ''),
                'datos_complementarios' => $this->assessmentComplementaryDefaults('acupuntura'),
            ],
        ];
    }

    /**
     * @param string $formKey
     * @return array
     */
    // Indica qué campos de la evaluación se guardan como listas separadas por líneas; solo acupuntura tiene estos campos.
    //   Uso:
    //     $fields = $this->assessmentArrayFields('acupuntura');
    //   Resultado: Devuelve los nombres de campos que deben convertirse en arreglos.
    private function assessmentArrayFields(string $formKey): array
    {
        return $formKey === 'acupuntura' ? [
            'lengua_forma',
            'lengua_color',
            'lengua_otros_rasgos',
            'saburra_color',
            'saburra_espesor',
            'saburra_distribucion',
            'diagnostico_8_reglas',
            'diagnostico_6_niveles',
            'diagnostico_4_capas',
            'organos_zang_fu',
            'puntos_sugeridos',
        ] : [];
    }

    /**
     * @param string $field
     * @return array
     */
    // Construye las reglas de validación permitidas para las opciones enumeradas de calidad de sueño, desplazamiento, trabajo o recreación.
    //   Uso:
    //     $rules = $this->enumRules('calidad_sueno');
    //   Resultado: Devuelve una regla nullable con las opciones válidas de sueño.
    private function enumRules(string $field): array
    {
        return [
            'nullable',
            'in:'.implode(',', match ($field) {
                'calidad_sueno' => ['bueno', 'ligero', 'moderado', 'malo'],
                'desplazamiento' => ['sin_dolor', 'ligero', 'moderado', 'severo'],
                'tiempo_trabajo' => [
                    'tiempo_normal',
                    'se_extralimita',
                    'no_se_extralimita',
                    'no_puede_trabajar',
                ],
                'recreacion' => [
                    'todas_actividades',
                    'algunas_actividades',
                    'pocas_actividades',
                    'ninguna_actividad',
                ],
            }),
        ];
    }

    /**
     * @param ?string $value
     * @return array
     */
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
