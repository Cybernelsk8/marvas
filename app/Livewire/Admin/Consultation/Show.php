<?php

namespace App\Livewire\Admin\Consultation;

use App\Models\AnamnesisGeneral;
use App\Models\Consulta;
use App\Services\ConsultationService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

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
        'observaciones_profesional' => '',
    ];

    public array $assessmentForms = [];

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
        ]);

        $anamnesis = $this->consultation->anamnesisGeneral;

        if ($anamnesis) {
            foreach (array_keys($this->form) as $field) {
                $value = $anamnesis->{$field};
                $this->form[$field] = is_array($value)
                    ? implode(PHP_EOL, $value)
                    : (string) ($value ?? '');
            }
        }

        $this->assessmentForms = $this->assessmentFormDefaults();
        foreach ([
            'homeopatia' => $anamnesis?->homeopatica,
            'quiropraxia' => $anamnesis?->quiropraxia,
            'acupuntura' => $anamnesis?->acupuntura,
        ] as $formKey => $record) {
            foreach ($this->assessmentForms[$formKey] as $field => $default) {
                $value = $record?->{$field};
                $this->assessmentForms[$formKey][$field] = is_array($value)
                    ? implode(PHP_EOL, $value)
                    : ($value ?? $default);
            }
        }
    }

    public function save(ConsultationService $consultationService): void
    {
        $this->authorize('consultations.edit');

        $anamnesis = $consultationService->saveGeneralAnamnesis(
            $this->consultation->id,
            $this->validatedAnamnesis(),
            $this->validatedAssessmentForms(),
        );
        $this->consultation->setRelation('anamnesisGeneral', $anamnesis);

        Flux::toast(text: 'Anamnesis guardada correctamente.', variant: 'success');
    }

    public function closeConsultation(ConsultationService $consultationService): void
    {
        $this->authorize('consultations.close');

        $this->consultation = $consultationService->closeConsultation(
            $this->consultation->id,
            $this->validatedAnamnesis(closing: true),
            $this->validatedAssessmentForms(),
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
        ]);
    }

    private function validatedAnamnesis(bool $closing = false): array
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
            'form.observaciones_profesional' => [
                $closing ? 'required' : 'nullable',
                'string',
                'max:10000',
            ],
        ];

        $validated = $this->validate($rules)['form'];

        foreach (['antecedentes_patologicos', 'habitos'] as $field) {
            $validated[$field] = $this->linesToArray($validated[$field] ?? null);
        }

        foreach ($validated as $field => $value) {
            if ($field !== 'antecedentes_patologicos' && $field !== 'habitos') {
                $validated[$field] = $value === '' ? null : $value;
            }
        }

        return $validated;
    }

    private function validatedAssessmentForms(): array
    {
        $rules = [];
        foreach ($this->assessmentFormDefaults() as $formKey => $fields) {
            foreach ($fields as $field => $default) {
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
        }

        $validated = $this->validate($rules)['assessmentForms'] ?? [];
        foreach ($this->assessmentFormDefaults() as $formKey => $fields) {
            $validated[$formKey] ??= [];
            foreach ($fields as $field => $default) {
                $value = $validated[$formKey][$field] ?? $default;
                if (in_array($field, $this->assessmentArrayFields('acupuntura'), true)) {
                    $validated[$formKey][$field] = $this->linesToArray($value);
                } elseif (is_bool($default)) {
                    $validated[$formKey][$field] = (bool) $value;
                } elseif ($value === '') {
                    $validated[$formKey][$field] = null;
                }
            }
        }

        return $validated;
    }

    private function assessmentFormDefaults(): array
    {
        return [
            'homeopatia' => array_fill_keys([
                'aspecto_paciente',
                'sintomas_locales_generales',
                'sintomas_mentales',
                'sintomas_biopatograficos',
                'diagnostico_posible',
                'tratamiento_indicado',
                'observaciones',
            ], ''),
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
            ],
        ];
    }

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
