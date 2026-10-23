<?php

namespace App\Livewire;

use App\Livewire\Traits\HasCitas;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Sucursal;
use App\Models\SucursalEvaluadorMbi;
use App\Services\AppointmentManagementService;
use App\Services\ClinicAvailabilityService;
use App\Services\PatientMatchService;
use App\Services\PatientMergeService;
use App\Traits\DataTable;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Solicitudes')]
class Solicitud extends Component
{
    use DataTable, HasCitas;

    public array $headers = [
        ['index' => 'expediente.id', 'label' => 'Expediente #', 'type' => 'numeric'],
        ['index' => 'expediente.paciente.nombre_completo', 'label' => 'Paciente'],
        ['index' => 'expediente.paciente.nombres', 'label' => 'Nombres', 'exclude' => true],
        ['index' => 'fecha_hora_inicio', 'label' => 'Fecha y Hora', 'type' => 'datetime'],
        ['index' => 'estado', 'label' => 'Estado'],
        ['index' => 'tipo_cita', 'label' => 'Tipo Cita'],
    ];

    public array $filtersOptions = [];

    public array $solicitud = [];

    public array $reprogramacion = ['fecha' => null, 'hora' => null];

    public string $motivoCancelacion = '';

    public int $solicitudId = 0;

    public ?string $sucursalConfirmacion = null;

    public ?string $profesionalConfirmacion = null;

    public ?string $especialidadConfirmacion = null;

    public bool $mbiEvaluatorRequired = false;

    public ?string $formularioUrl = null;

    public ?string $formularioNombre = null;

    public ?int $selectedExistingPatientId = null;

    public bool $confirmPatientReuse = false;

    #[Computed]
    public function rows()
    {
        return Cita::query()
            ->with(['expediente.paciente', 'sucursal'])
            ->filterAdvance($this->headers, [
                'search' => $this->search,
                'sort' => [
                    'field' => $this->sortBy,
                    'direction' => $this->sortDirection,
                ],
                'filters' => $this->processFilters(),
            ])
            ->paginate($this->per_page);
    }

    public function mount(): void
    {
        $this->sortBy = 'fecha_hora_inicio';
        $this->filters = [['field' => 'estado', 'operator' => '=', 'value' => 'solicitada']];

        $this->filtersOptions = [
            [
                'label' => 'Ordenar por',
                'icon' => 'bars-arrow-down',
                'type' => 'sort',
                'options' => [
                    ['value' => ['field' => 'fecha_hora_inicio', 'direction' => 'desc'], 'label' => 'Más recientes primero'],
                    ['value' => ['field' => 'fecha_hora_inicio', 'direction' => 'asc'], 'label' => 'Más antiguas primero'],
                    ['value' => ['field' => 'expediente.paciente.nombres', 'direction' => 'asc'], 'label' => 'Nombre (A-Z)'],
                    ['value' => ['field' => 'expediente.paciente.nombres', 'direction' => 'desc'], 'label' => 'Nombre (Z-A)'],
                    ['value' => null, 'label' => 'Restaurar'],
                ],
            ],
            [
                'label' => 'Fecha de solicitud',
                'icon' => 'calendar',
                'type' => 'filter',
                'options' => [
                    ['value' => ['field' => 'created_at', 'operator' => 'like', 'value' => now()->subDay()->format('Y-m-d')], 'label' => 'Ayer'],
                    ['value' => ['field' => 'created_at', 'operator' => 'like', 'value' => now()->format('Y-m-d')], 'label' => 'Hoy'],
                    ['value' => null, 'label' => 'Todas'],
                ],
            ],
            [
                'label' => 'Por estado',
                'icon' => 'flag',
                'type' => 'filter',
                'options' => [
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'solicitada'], 'label' => 'Solicitada'],
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'agendada'], 'label' => 'Agendada'],
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'confirmada'], 'label' => 'Confirmada'],
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'en_atencion'], 'label' => 'En atención'],
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'finalizada'], 'label' => 'Finalizada'],
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'cancelada'], 'label' => 'Cancelada'],
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'reprogramada'], 'label' => 'Reprogramada'],
                    ['value' => null, 'label' => 'Todas'],
                ],
            ],
            [
                'label' => 'Por tipo',
                'icon' => 'tag',
                'type' => 'filter',
                'options' => [
                    ['value' => ['field' => 'tipo_cita', 'operator' => '=', 'value' => 'primera_vez'], 'label' => 'Primera vez'],
                    ['value' => ['field' => 'tipo_cita', 'operator' => '=', 'value' => 'seguimiento'], 'label' => 'Seguimiento'],
                    ['value' => null, 'label' => 'Todos'],
                ],
            ],
        ];

    }

    public function render()
    {
        return view('livewire.solicitud', [
            'sucursalesActivas' => Sucursal::query()->where('activa', true)->orderBy('nombre')->get(),
            'profesionalesConfirmacion' => $this->profesionalesPorSucursal((int) ($this->sucursalConfirmacion ?? 0)),
            'especialidadesConfirmacion' => $this->especialidadesPorProfesional((int) ($this->profesionalConfirmacion ?? 0)),
            'horariosReprogramacion' => $this->reprogramacion['fecha']
                ? $this->getAvailableHours(
                    $this->reprogramacion['fecha'],
                    availabilityConfiguration: app(ClinicAvailabilityService::class)->configuration(),
                    ignoreAppointmentId: $this->solicitudId,
                )
                : [],
        ]);
    }

    public function updatedReprogramacionFecha(?string $fecha): void
    {
        if (! $fecha || ! in_array(
            $this->reprogramacion['hora'] ?? null,
            $this->getAvailableHours(
                $fecha,
                availabilityConfiguration: app(ClinicAvailabilityService::class)->configuration(),
                ignoreAppointmentId: $this->solicitudId,
            ),
            true,
        )) {
            $this->reprogramacion['hora'] = null;
        }
    }

    public function showSolicitud(int $id): void
    {
        $this->authorize('page.view.requests');

        $cita = Cita::with(['expediente.paciente'])->findOrFail($id);
        $this->solicitudId = $cita->id;
        $this->solicitud = $cita->toArray();
        $this->selectedExistingPatientId = null;
        $this->confirmPatientReuse = false;
        $this->resetValidation();
        Flux::modal('solicitud-modal')->show();
    }

    #[Computed]
    public function possiblePatientMatches()
    {
        $patient = $this->solicitud['expediente']['paciente'] ?? [];

        if (! is_array($patient)
            || $patient === []
            || ! in_array($this->solicitud['estado'] ?? null, ['solicitada', 'reprogramada'], true)) {
            return collect();
        }

        return app(PatientMatchService::class)->matchingCandidates(
            $patient,
            isset($patient['id']) ? (int) $patient['id'] : null,
        );
    }

    public function updatedSolicitud(): void
    {
        $this->selectedExistingPatientId = null;
        $this->confirmPatientReuse = false;
    }

    public function selectExistingPatient(int $patientId): void
    {
        $this->authorize('requests.edit');

        abort_if(! $this->possiblePatientMatches->contains('id', $patientId), 404);

        $this->selectedExistingPatientId = $patientId;
        $this->confirmPatientReuse = false;
        $this->resetValidation('selectedExistingPatientId');
        $this->resetValidation('confirmPatientReuse');
    }

    public function reuseExistingPatient(): void
    {
        $this->authorize('requests.edit');

        $this->validate([
            'selectedExistingPatientId' => ['required', 'integer'],
            'confirmPatientReuse' => ['accepted'],
            'solicitud.expediente.paciente.nombres' => ['required', 'string', 'max:150'],
            'solicitud.expediente.paciente.apellidos' => ['required', 'string', 'max:150'],
            'solicitud.expediente.paciente.fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'solicitud.expediente.paciente.sexo' => ['required', Rule::in(['M', 'F', 'otro'])],
            'solicitud.expediente.paciente.estado_civil' => ['nullable', Rule::in(['soltero', 'casado', 'divorciado', 'viudo', 'union_libre', 'otro'])],
            'solicitud.expediente.paciente.religion' => ['nullable', 'string', 'max:60'],
            'solicitud.expediente.paciente.numero_dpi' => ['nullable', 'string', 'max:20'],
            'solicitud.expediente.paciente.telefono' => ['nullable', 'string', 'max:20'],
            'solicitud.expediente.paciente.telefono_emergencia' => ['nullable', 'string', 'max:20'],
            'solicitud.expediente.paciente.email' => ['nullable', 'email', 'max:191'],
            'solicitud.expediente.paciente.direccion' => ['nullable', 'string'],
            'solicitud.expediente.paciente.ocupacion' => ['nullable', 'string', 'max:100'],
        ]);

        $sourcePatientId = (int) data_get($this->solicitud, 'expediente.paciente.id');
        $submittedPatient = data_get($this->solicitud, 'expediente.paciente', []);
        $dpiOwner = filled($submittedPatient['numero_dpi'] ?? null)
            ? Paciente::query()->where('numero_dpi', $submittedPatient['numero_dpi'])->value('id')
            : null;

        if ($dpiOwner !== null && (int) $dpiOwner !== $sourcePatientId && (int) $dpiOwner !== $this->selectedExistingPatientId) {
            $this->addError('solicitud.expediente.paciente.numero_dpi', 'El DPI ya pertenece a otro expediente. Selecciona ese paciente como coincidencia para vincularlo.');

            return;
        }

        $result = app(PatientMergeService::class)->mergeRequestPatient(
            $this->solicitudId,
            $sourcePatientId,
            $this->selectedExistingPatientId,
            $submittedPatient,
        );

        $conflictCount = count($result['profile_conflicts']) + count($result['expediente_conflicts']);
        $message = 'La solicitud se vinculó al expediente de '.$result['target']->nombre_completo.'.';

        if ($conflictCount > 0) {
            $message .= ' Los datos en conflicto se conservaron en el registro de origen y en auditoría para revisión.';
        }

        $this->resetData();
        Flux::toast(text: $message, variant: 'success');
    }

    public function store(): void
    {
        $this->authorize('requests.edit');

        $cita = $this->selectedRequest();
        $paciente = $cita->expediente->paciente;

        $datos = $this->validate([
            'solicitud.expediente.paciente.nombres' => ['required', 'string', 'max:150'],
            'solicitud.expediente.paciente.apellidos' => ['required', 'string', 'max:150'],
            'solicitud.expediente.paciente.fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'solicitud.expediente.paciente.sexo' => ['required', Rule::in(['M', 'F', 'otro'])],
            'solicitud.expediente.paciente.estado_civil' => ['nullable', Rule::in(['soltero', 'casado', 'divorciado', 'viudo', 'union_libre', 'otro'])],
            'solicitud.expediente.paciente.religion' => ['nullable', 'string', 'max:60'],
            'solicitud.expediente.paciente.numero_dpi' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('pacientes', 'numero_dpi')->ignore($paciente->id),
            ],
            'solicitud.expediente.paciente.telefono' => ['nullable', 'string', 'max:20'],
            'solicitud.expediente.paciente.telefono_emergencia' => ['nullable', 'string', 'max:20'],
            'solicitud.expediente.paciente.email' => ['nullable', 'email', 'max:191'],
            'solicitud.expediente.paciente.direccion' => ['nullable', 'string'],
            'solicitud.expediente.paciente.ocupacion' => ['nullable', 'string', 'max:100'],
        ]);

        $datosPaciente = $datos['solicitud']['expediente']['paciente'];

        $paciente->fill(Arr::only($datosPaciente, [
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
        ]));
        $paciente->save();

        $this->solicitud = $cita->fresh(['expediente.paciente'])->toArray();

        Flux::toast(text: 'Datos del paciente guardados correctamente.', variant: 'success');
    }

    public function confirmar(int $id): void
    {
        $this->authorize('requests.confirm');

        $datos = $this->validate([
            'sucursalConfirmacion' => [
                'required',
                'integer',
                Rule::exists('sucursales', 'id')->where('activa', true),
            ],
            'profesionalConfirmacion' => [
                'required',
                'integer',
                Rule::exists('profesionales', 'id')->where('activo', true),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->sucursalConfirmacion || ! $value) {
                        return;
                    }

                    $exists = Profesional::query()
                        ->whereKey((int) $value)
                        ->where('activo', true)
                        ->whereHas('sucursales', fn ($query) => $query->whereKey((int) $this->sucursalConfirmacion))
                        ->exists();

                    if (! $exists) {
                        $fail('El profesional no pertenece a la sede seleccionada.');
                    }
                },
            ],
            'especialidadConfirmacion' => [
                'required',
                'integer',
                Rule::exists('especialidades', 'id')->where('activa', true),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->profesionalConfirmacion || ! $value) {
                        return;
                    }

                    $exists = Profesional::query()
                        ->whereKey((int) $this->profesionalConfirmacion)
                        ->where('activo', true)
                        ->whereHas('especialidades', fn ($query) => $query->whereKey((int) $value)->where('activa', true))
                        ->exists();

                    if (! $exists) {
                        $fail('La especialidad no está asociada al profesional seleccionado.');
                    }
                },
            ],
        ], [
            'sucursalConfirmacion.required' => 'Selecciona la sede donde se atenderá la cita.',
            'sucursalConfirmacion.exists' => 'La sede seleccionada no está activa. Selecciona otra.',
            'profesionalConfirmacion.required' => 'Selecciona el profesional que atenderá la cita.',
            'profesionalConfirmacion.exists' => 'El profesional seleccionado no está activo.',
            'especialidadConfirmacion.required' => 'Selecciona la especialidad de atención.',
            'especialidadConfirmacion.exists' => 'La especialidad seleccionada no está activa.',
        ]);
        $sucursalId = (int) $datos['sucursalConfirmacion'];

        $resultado = app(AppointmentManagementService::class)->confirm(
            $id,
            $sucursalId,
            Auth::id(),
            (int) $datos['profesionalConfirmacion'],
            (int) $datos['especialidadConfirmacion'],
        );

        if ($resultado !== 'confirmada') {
            Flux::toast(
                text: match ($resultado) {
                    'no_pendiente' => 'La solicitud ya no se encuentra pendiente.',
                    'sede_no_disponible' => 'La sede seleccionada dejó de estar activa. Selecciona otra sede.',
                    'profesional_no_disponible' => 'El profesional seleccionado no está disponible para esa sede.',
                    'evaluador_no_disponible' => 'La sede no tiene configurado ese profesional como Evaluador MBI.',
                    'especialidad_no_disponible' => 'La especialidad ya no está activa o no corresponde al profesional.',
                    default => 'El horario ya no está disponible. Reprograma la solicitud antes de confirmarla.',
                },
                variant: 'danger',
            );

            return;
        }

        $this->reset(['sucursalConfirmacion', 'profesionalConfirmacion', 'especialidadConfirmacion', 'mbiEvaluatorRequired']);
        Flux::modals()->close();
        Flux::toast(text: 'La cita fue confirmada correctamente.', variant: 'success');
    }

    public function prepararConfirmacion(int $id): void
    {
        $this->authorize('requests.confirm');

        $cita = Cita::query()->with('sucursal')->findOrFail($id);

        if (! in_array($cita->estado, ['solicitada', 'reprogramada', 'agendada'], true)) {
            Flux::toast(text: 'La solicitud ya no se encuentra pendiente.', variant: 'danger');

            return;
        }

        $this->solicitudId = $cita->id;
        $this->mbiEvaluatorRequired = $cita->tipo_cita === 'primera_vez';
        $this->sucursalConfirmacion = $cita->sucursal_id ? (string) $cita->sucursal_id : null;
        $this->profesionalConfirmacion = $cita->profesional_id ? (string) $cita->profesional_id : null;
        $this->especialidadConfirmacion = $cita->especialidad_id ? (string) $cita->especialidad_id : null;
        $this->selectOnlyAvailableAssignments();
        $this->resetValidation(['sucursalConfirmacion', 'profesionalConfirmacion', 'especialidadConfirmacion']);
        Flux::modal('confirmar-cita-modal')->show();
    }

    public function updatedSucursalConfirmacion(?string $sucursalId): void
    {
        $this->profesionalConfirmacion = null;
        $this->especialidadConfirmacion = null;
        $this->resetValidation(['profesionalConfirmacion', 'especialidadConfirmacion']);

        if ($sucursalId !== null && $sucursalId !== '') {
            $this->validateOnly('sucursalConfirmacion', [
                'sucursalConfirmacion' => [
                    'required',
                    'integer',
                    Rule::exists('sucursales', 'id')->where('activa', true),
                ],
            ]);

            $professionals = $this->profesionalesPorSucursal((int) $sucursalId);

            if ($professionals->count() === 1) {
                $this->profesionalConfirmacion = (string) $professionals->first()->id;
                $this->updatedProfesionalConfirmacion($this->profesionalConfirmacion);
            }
        }
    }

    public function updatedProfesionalConfirmacion(?string $profesionalId): void
    {
        $this->especialidadConfirmacion = null;
        $this->resetValidation('especialidadConfirmacion');

        if ($profesionalId !== null && $profesionalId !== '') {
            $this->validateOnly('profesionalConfirmacion', [
                'profesionalConfirmacion' => ['required', 'integer', Rule::exists('profesionales', 'id')->where('activo', true)],
            ]);

            $specialties = $this->especialidadesPorProfesional((int) $profesionalId);

            if ($specialties->count() === 1) {
                $this->especialidadConfirmacion = (string) $specialties->first()->id;
            }
        }
    }

    public function profesionalesPorSucursal(?int $sucursalId)
    {
        if (! $sucursalId) {
            return collect();
        }

        $evaluatorId = $this->mbiEvaluatorRequired
            ? SucursalEvaluadorMbi::query()->where('sucursal_id', $sucursalId)->value('profesional_id')
            : null;

        return Profesional::query()
            ->where('activo', true)
            ->whereHas('sucursales', fn ($query) => $query->whereKey($sucursalId))
            ->when($this->mbiEvaluatorRequired, fn ($query) => $query
                ->whereKey($evaluatorId ?? 0)
                ->whereHas('especialidades', fn ($specialties) => $specialties->where('slug', 'evaluacion-mbi')->where('activa', true)))
            ->orderBy('nombre_completo')
            ->get();
    }

    private function selectOnlyAvailableAssignments(): void
    {
        if (! $this->sucursalConfirmacion) {
            $branches = Sucursal::query()->where('activa', true)->orderBy('nombre')->get(['id']);

            if ($branches->count() === 1) {
                $this->sucursalConfirmacion = (string) $branches->first()->id;
            }
        }

        if ($this->mbiEvaluatorRequired) {
            $evaluatorId = $this->sucursalConfirmacion
                ? SucursalEvaluadorMbi::query()->where('sucursal_id', (int) $this->sucursalConfirmacion)->value('profesional_id')
                : null;
            $this->profesionalConfirmacion = $evaluatorId ? (string) $evaluatorId : null;
            $mbiSpecialtyId = Especialidad::query()
                ->where('slug', 'evaluacion-mbi')
                ->where('activa', true)
                ->value('id');
            $this->especialidadConfirmacion = $this->profesionalConfirmacion && $mbiSpecialtyId
                ? (string) $mbiSpecialtyId
                : null;

            return;
        }

        if (! $this->profesionalConfirmacion && $this->sucursalConfirmacion) {
            $professionals = $this->profesionalesPorSucursal((int) $this->sucursalConfirmacion);

            if ($professionals->count() === 1) {
                $this->profesionalConfirmacion = (string) $professionals->first()->id;
            }
        }

        if (! $this->especialidadConfirmacion && $this->profesionalConfirmacion) {
            $specialties = $this->especialidadesPorProfesional((int) $this->profesionalConfirmacion);

            if ($specialties->count() === 1) {
                $this->especialidadConfirmacion = (string) $specialties->first()->id;
            }
        }
    }

    public function especialidadesPorProfesional(?int $profesionalId)
    {
        if (! $profesionalId) {
            return collect();
        }

        return Especialidad::query()
            ->where('activa', true)
            ->when($this->mbiEvaluatorRequired, fn ($query) => $query->where('slug', 'evaluacion-mbi'))
            ->whereHas('profesionales', fn ($query) => $query->whereKey($profesionalId)->where('activo', true))
            ->orderBy('nombre')
            ->get();
    }

    public function generarLinkFormulario(int $id): void
    {
        $this->authorize('requests.edit');

        $cita = Cita::query()->findOrFail($id);
        $this->solicitudId = $cita->id;
        $this->formularioNombre = $cita->expediente->paciente->nombre_completo;
        $this->formularioUrl = URL::temporarySignedRoute(
            'formulario-paciente',
            now()->addDays(7),
            ['cita' => $cita->id],
        );

        Flux::modal('formulario-paciente-link-modal')->show();
    }

    public function prepararReprogramacion(int $id): void
    {
        $this->authorize('requests.reschedule');

        $cita = Cita::query()->findOrFail($id);

        if ($cita->estado !== 'solicitada') {
            $this->addError('solicitud', 'Solo se pueden reprogramar solicitudes pendientes.');

            return;
        }

        $this->solicitudId = $cita->id;
        $this->reprogramacion = [
            'fecha' => $cita->fecha_hora_inicio->toDateString(),
            'hora' => $cita->fecha_hora_inicio->format('H:i'),
        ];
        Flux::modal('reprogramar-cita-modal')->show();
    }

    public function reprogramar(): void
    {
        $this->authorize('requests.reschedule');

        $datos = $this->validate([
            'reprogramacion.fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reprogramacion.hora' => ['required', 'date_format:H:i'],
        ]);
        $cita = $this->selectedRequest();
        $inicio = Carbon::createFromFormat('!Y-m-d H:i', $datos['reprogramacion']['fecha'].' '.$datos['reprogramacion']['hora']);
        $resultado = app(AppointmentManagementService::class)->reschedule($cita->id, $inicio);

        if ($resultado !== 'reprogramada') {
            $this->addError(
                'reprogramacion.hora',
                match ($resultado) {
                    'sin_configuracion' => 'No se puede reprogramar: falta configurar los horarios de atención.',
                    'no_pendiente' => 'La solicitud ya no se encuentra pendiente.',
                    default => 'El horario seleccionado ya no está disponible. Elige otro.',
                },
            );

            return;
        }

        $this->reset(['reprogramacion', 'solicitudId']);
        Flux::modals()->close();
        Flux::toast(text: 'La solicitud fue reprogramada y sigue pendiente de confirmación.', variant: 'success');
    }

    public function prepararCancelacion(int $id): void
    {
        $this->authorize('requests.cancel');

        $cita = Cita::query()->findOrFail($id);

        if ($cita->estado !== 'solicitada') {
            $this->addError('solicitud', 'Solo se pueden cancelar solicitudes pendientes desde esta bandeja.');

            return;
        }

        $this->solicitudId = $cita->id;
        $this->motivoCancelacion = '';
        Flux::modal('cancelar-cita-modal')->show();
    }

    public function cancelar(): void
    {
        $this->authorize('requests.cancel');

        $datos = $this->validate([
            'motivoCancelacion' => ['required', 'string', 'max:2000'],
        ]);
        $resultado = app(AppointmentManagementService::class)->cancel($this->solicitudId, $datos['motivoCancelacion']);

        if ($resultado !== 'cancelada') {
            $this->addError('motivoCancelacion', 'La solicitud ya no se encuentra pendiente.');

            return;
        }

        $this->reset(['motivoCancelacion', 'solicitudId']);
        Flux::modals()->close();
        Flux::toast(text: 'La solicitud fue cancelada.', variant: 'success');
    }

    public function resetData(): void
    {
        $this->reset([
            'solicitud',
            'solicitudId',
            'sucursalConfirmacion',
            'profesionalConfirmacion',
            'especialidadConfirmacion',
            'mbiEvaluatorRequired',
            'reprogramacion',
            'motivoCancelacion',
            'formularioUrl',
            'formularioNombre',
            'selectedExistingPatientId',
            'confirmPatientReuse',
        ]);
        Flux::modals()->close();
    }

    private function selectedRequest(): Cita
    {
        abort_if($this->solicitudId < 1, 404);

        return Cita::with(['expediente.paciente'])->findOrFail($this->solicitudId);
    }
}
