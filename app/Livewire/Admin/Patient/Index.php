<?php

namespace App\Livewire\Admin\Patient;

use App\Models\Auditoria;
use App\Models\Paciente;
use App\Services\PatientMergeService;
use App\Services\PatientMatchService;
use App\Traits\DataTable;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pacientes')]
class Index extends Component
{
    use DataTable;

    public string $status = 'active';

    public array $patientForm = [];

    public bool $confirmDistinctPatient = false;

    public ?int $editingPatientId = null;

    public ?int $mergeSourcePatientId = null;

    public ?int $mergeTargetPatientId = null;

    public string $mergeTargetSearch = '';

    public array $mergeProfileChoices = [];

    public array $mergeExpedienteChoices = [];

    public bool $confirmPatientMerge = false;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'type' => 'numeric'],
        ['index' => 'expediente.id', 'label' => 'Expediente #', 'type' => 'numeric'],
        ['index' => 'nombre_completo', 'label' => 'Paciente'],
        ['index' => 'numero_dpi', 'label' => 'DPI'],
        ['index' => 'telefono', 'label' => 'Teléfono', 'type' => 'phone'],
        ['index' => 'email', 'label' => 'Correo'],
        ['index' => 'activo', 'label' => 'Estado', 'type' => 'boolean'],
        ['index' => 'actions', 'label' => ''],
    ];

    public function mount(): void
    {
        $this->authorize('page.view.patients');
        $this->sortBy = 'nombre_completo';
    }

    public function createPatient(): void
    {
        $this->authorize('patients.create');
        $this->resetPatientForm();
        Flux::modal('patient-form')->show();
    }

    public function editPatient(int $patientId): void
    {
        $this->authorize('patients.edit');
        $patient = Paciente::query()->findOrFail($patientId);

        $this->editingPatientId = $patient->id;
        $this->patientForm = $patient->only([
            'nombres',
            'apellidos',
            'fecha_nacimiento',
            'sexo',
            'estado_civil',
            'religion',
            'numero_dpi',
            'telefono',
            'telefono_emergencia',
            'email',
            'direccion',
            'ocupacion',
            'como_nos_conocio',
        ]);
        $this->patientForm['fecha_nacimiento'] = $patient->fecha_nacimiento?->format('Y-m-d');
        $this->confirmDistinctPatient = false;
        $this->resetValidation();
        Flux::modal('patient-form')->show();
    }

    public function updatedPatientForm(): void
    {
        $this->confirmDistinctPatient = false;
    }

    #[Computed]
    public function possibleMatches()
    {
        return app(PatientMatchService::class)->matchingCandidates(
            $this->patientForm,
            $this->editingPatientId,
        );
    }

    public function startPatientMerge(int $patientId): void
    {
        $this->authorize('patients.merge');
        $patient = Paciente::query()
            ->where('activo', true)
            ->whereNull('fusionado_en_paciente_id')
            ->findOrFail($patientId);

        $this->resetMergeForm();
        $this->mergeSourcePatientId = $patient->id;
        Flux::modal('patient-merge')->show();
    }

    #[Computed]
    public function mergeTargetCandidates()
    {
        $this->authorize('patients.merge');

        if ($this->mergeSourcePatientId === null || mb_strlen(trim($this->mergeTargetSearch)) < 2) {
            return collect();
        }

        $search = mb_strtolower(trim($this->mergeTargetSearch));
        $fullNameExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "LOWER(nombres || ' ' || apellidos)"
            : "LOWER(CONCAT(nombres, ' ', apellidos))";

        return Paciente::query()
            ->where('activo', true)
            ->whereNull('fusionado_en_paciente_id')
            ->whereKeyNot($this->mergeSourcePatientId)
            ->where(function (Builder $query) use ($search, $fullNameExpression): void {
                $query->whereRaw('LOWER(nombres) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(apellidos) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw($fullNameExpression.' LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(COALESCE(numero_dpi, \'\')) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(COALESCE(telefono, \'\')) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', ['%'.$search.'%']);
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function mergePreview(): ?array
    {
        $this->authorize('patients.merge');

        if ($this->mergeSourcePatientId === null || $this->mergeTargetPatientId === null) {
            return null;
        }

        return app(PatientMergeService::class)->previewManualMerge(
            $this->mergeSourcePatientId,
            $this->mergeTargetPatientId,
        );
    }

    public function updatedMergeTargetSearch(): void
    {
        $this->mergeTargetPatientId = null;
        $this->mergeProfileChoices = [];
        $this->mergeExpedienteChoices = [];
        $this->confirmPatientMerge = false;
        unset($this->mergePreview);
    }

    public function selectMergeTarget(int $patientId): void
    {
        $this->authorize('patients.merge');
        abort_if(! $this->mergeTargetCandidates->contains('id', $patientId), 404);

        $this->mergeTargetPatientId = $patientId;
        $this->mergeProfileChoices = [];
        $this->mergeExpedienteChoices = [];
        $this->confirmPatientMerge = false;
        $this->resetValidation();
        unset($this->mergePreview);
    }

    public function mergePatientsManually(): void
    {
        $this->authorize('patients.merge');
        $preview = $this->mergePreview;
        abort_if($preview === null, 404);

        $rules = [
            'mergeSourcePatientId' => ['required', 'integer'],
            'mergeTargetPatientId' => ['required', 'integer', 'different:mergeSourcePatientId'],
            'confirmPatientMerge' => ['accepted'],
        ];

        foreach (array_keys($preview['profileConflicts']) as $field) {
            $rules['mergeProfileChoices.'.$field] = ['required', Rule::in(['source', 'target'])];
        }

        foreach (array_keys($preview['expedienteConflicts']) as $field) {
            $rules['mergeExpedienteChoices.'.$field] = ['required', Rule::in(['source', 'target'])];
        }

        $this->validate($rules);

        $result = app(PatientMergeService::class)->mergeManually(
            $this->mergeSourcePatientId,
            $this->mergeTargetPatientId,
            $this->mergeProfileChoices,
            $this->mergeExpedienteChoices,
        );

        $this->resetMergeForm();
        Flux::modals()->close();
        Flux::toast(
            text: 'Se fusionó el expediente de '.$result['target']->nombre_completo.' y el registro de origen quedó inactivo y vinculado.',
            variant: 'success',
        );
        $this->resetPage();
    }

    public function resetMergeForm(): void
    {
        $this->mergeSourcePatientId = null;
        $this->mergeTargetPatientId = null;
        $this->mergeTargetSearch = '';
        $this->mergeProfileChoices = [];
        $this->mergeExpedienteChoices = [];
        $this->confirmPatientMerge = false;
        $this->resetValidation();
        unset($this->mergePreview);
    }

    public function mergeProfileFieldLabel(string $field): string
    {
        return match ($field) {
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'fecha_nacimiento' => 'Fecha de nacimiento',
            'sexo' => 'Sexo',
            'estado_civil' => 'Estado civil',
            'religion' => 'Religión',
            'numero_dpi' => 'DPI',
            'telefono' => 'Teléfono',
            'telefono_emergencia' => 'Teléfono de emergencia',
            'email' => 'Correo',
            'direccion' => 'Dirección',
            'ocupacion' => 'Ocupación',
            'como_nos_conocio' => 'Cómo nos conoció',
            'clasificacion' => 'Clasificación',
            'objetivo_principal' => 'Objetivo principal',
            'fase_terapeutica' => 'Fase terapéutica',
            'estado' => 'Estado del expediente',
            'notas_generales' => 'Notas generales',
            default => $field,
        };
    }

    public function savePatient(): void
    {
        $this->authorize($this->editingPatientId ? 'patients.edit' : 'patients.create');

        $patientId = $this->editingPatientId;
        $data = $this->validate([
            'patientForm.nombres' => ['required', 'string', 'max:150'],
            'patientForm.apellidos' => ['required', 'string', 'max:150'],
            'patientForm.fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'patientForm.sexo' => ['required', Rule::in(['M', 'F', 'otro'])],
            'patientForm.estado_civil' => ['nullable', Rule::in(['soltero', 'casado', 'divorciado', 'viudo', 'union_libre', 'otro'])],
            'patientForm.religion' => ['nullable', 'string', 'max:60'],
            'patientForm.numero_dpi' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('pacientes', 'numero_dpi')->ignore($patientId),
            ],
            'patientForm.telefono' => ['nullable', 'string', 'max:20'],
            'patientForm.telefono_emergencia' => ['nullable', 'string', 'max:20'],
            'patientForm.email' => ['nullable', 'email', 'max:191'],
            'patientForm.direccion' => ['nullable', 'string'],
            'patientForm.ocupacion' => ['nullable', 'string', 'max:100'],
            'patientForm.como_nos_conocio' => ['nullable', 'string', 'max:100'],
        ]);

        $patientData = $this->normalizePatientData($data['patientForm']);
        $matches = app(PatientMatchService::class)->matchingCandidates($patientData, $patientId);

        if ($patientId === null && $matches->isNotEmpty() && ! $this->confirmDistinctPatient) {
            $this->addError('confirmDistinctPatient', 'Revisa las coincidencias. Si es otra persona, confirma explícitamente que deseas crear un nuevo registro.');

            return;
        }

        $patient = DB::transaction(function () use ($patientId, $patientData): Paciente {
            if ($patientId === null) {
                $patient = Paciente::query()->create($patientData);
                $patient->expediente()->firstOrCreate([]);

                Auditoria::query()->create([
                    'user_id' => Auth::id(),
                    'tabla_afectada' => 'pacientes',
                    'registro_id' => $patient->id,
                    'accion' => 'crear',
                    'datos_nuevos' => $patientData,
                    'ip' => request()->ip(),
                    'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
                ]);

                return $patient;
            }

            $patient = Paciente::query()->lockForUpdate()->findOrFail($patientId);
            $oldData = $patient->only(array_keys($patientData));
            $patient->fill($patientData)->save();
            $patient->expediente()->firstOrCreate([]);

            Auditoria::query()->create([
                'user_id' => Auth::id(),
                'tabla_afectada' => 'pacientes',
                'registro_id' => $patient->id,
                'accion' => 'editar',
                'datos_anteriores' => $oldData,
                'datos_nuevos' => $patientData,
                'ip' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
            ]);

            return $patient;
        });

        $this->resetPatientForm();
        Flux::modals()->close();
        Flux::toast(
            text: $patientId === null ? 'Paciente y expediente creados correctamente.' : 'Datos del paciente actualizados correctamente.',
            variant: 'success',
        );
        $this->resetPage();
    }

    public function resetPatientForm(): void
    {
        $this->patientForm = [
            'nombres' => '',
            'apellidos' => '',
            'fecha_nacimiento' => null,
            'sexo' => 'otro',
            'estado_civil' => null,
            'religion' => '',
            'numero_dpi' => '',
            'telefono' => '',
            'telefono_emergencia' => '',
            'email' => '',
            'direccion' => '',
            'ocupacion' => '',
            'como_nos_conocio' => '',
        ];
        $this->editingPatientId = null;
        $this->confirmDistinctPatient = false;
        $this->resetValidation();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function rows()
    {
        $query = Paciente::query()
            ->when($this->status !== 'all', function (Builder $query): void {
                $query->where('activo', $this->status === 'active');
            })
            ->filterAdvance($this->headers, [
                'sort' => [
                    'field' => $this->sortBy,
                    'direction' => $this->sortDirection,
                ],
                'filters' => $this->processFilters(),
            ]);

        if (trim($this->search) !== '') {
            $digits = preg_replace('/\D+/', '', $this->search);

            $query->where(function (Builder $query) use ($digits): void {
                $query->filterAdvance($this->headers, ['search' => $this->search]);

                if ($digits !== '') {
                    $query->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(COALESCE(pacientes.telefono, ''), '-', ''), ' ', ''), '+', '') LIKE ?",
                        ["%{$digits}%"],
                    );
                }
            });
        }

        return $query->paginate($this->per_page);
    }

    public function render()
    {
        return view('livewire.admin.patient.index');
    }

    private function normalizePatientData(array $data): array
    {
        foreach ($data as $field => $value) {
            if (is_string($value)) {
                $data[$field] = trim($value) === '' ? null : trim($value);
            }
        }

        $data['nombres'] = ucwords(mb_strtolower($data['nombres']));
        $data['apellidos'] = ucwords(mb_strtolower($data['apellidos']));
        $data['email'] = filled($data['email']) ? mb_strtolower($data['email']) : null;

        return $data;
    }
}
