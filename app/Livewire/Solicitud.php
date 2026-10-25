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

/**
 * Gestiona la bandeja de solicitudes de citas y sus acciones de recepción.
 */
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

    /**
     * Calcula las filas visibles según búsqueda, filtros y orden actuales.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Calcula las filas visibles según búsqueda, filtros y orden actuales.
     */
    // Calcula las filas visibles según búsqueda, filtros y orden actuales.
    //   Desde la vista: {{ $this->rows->total() }}
    //   Resultado: página actual de solicitudes con metadatos de paginación.
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

    /**
     * Livewire lo ejecuta al crear el componente para priorizar solicitudes pendientes recientes.
     * @return void Livewire lo ejecuta al crear el componente para priorizar solicitudes pendientes recientes.
     */
    // Livewire lo ejecuta al crear el componente para priorizar solicitudes pendientes recientes.
    //   Ejemplo: al abrir la pantalla queda aplicado estado=solicitada y se cargan filtros.
    //   Resultado: la tabla inicia ordenada por fecha descendente.
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

    /**
     * Livewire lo invoca en cada render para entregar sedes, opciones y horarios vigentes.
     * @return \Illuminate\Contracts\View\View Livewire lo invoca en cada render para entregar sedes, opciones y horarios vigentes.
     */
    // Livewire lo invoca en cada render para entregar sedes, opciones y horarios vigentes.
    //   Ejemplo: elegir fecha de reprogramación actualiza las horas incluidas en la vista.
    //   Resultado: la vista recibe colecciones y horarios disponibles.
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

    /**
     * Livewire lo ejecuta al cambiar reprogramacion.fecha para descartar horas obsoletas.
     * @param ?string $fecha Dato recibido por el componente.
     * @return void Livewire lo ejecuta al cambiar reprogramacion.fecha para descartar horas obsoletas.
     */
    // Livewire lo ejecuta al cambiar reprogramacion.fecha para descartar horas obsoletas.
    //   Desde la vista: <input type="date" wire:model.live="reprogramacion.fecha">
    //   Resultado: hora se limpia si no está disponible para la fecha nueva.
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

    /**
     * Carga el detalle de la cita, reinicia selección de paciente y abre su modal autorizado.
     * @param int $id Dato recibido por el componente.
     * @return void Carga el detalle de la cita, reinicia selección de paciente y abre su modal autorizado.
     */
    // Carga el detalle de la cita, reinicia selección de paciente y abre su modal autorizado.
    //   Desde la vista: <flux:button wire:click="showSolicitud({{ $row->id }})">Ver</flux:button>
    //   Resultado: solicitud-modal muestra el registro elegido con validaciones limpias.
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

    /**
     * Calcula candidatos que coinciden con el paciente de la solicitud pendiente.
     * @return \Illuminate\Support\Collection Calcula candidatos que coinciden con el paciente de la solicitud pendiente.
     */
    // Calcula candidatos que coinciden con el paciente de la solicitud pendiente.
    //   Desde la vista: @foreach ($this->possiblePatientMatches as $paciente) ... @endforeach
    //   Resultado: candidatos disponibles o colección vacía si la solicitud no aplica.
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

    /**
     * Reinicia selección y confirmación si cambian datos del formulario de solicitud.
     * @return void Reinicia selección y confirmación si cambian datos del formulario de solicitud.
     */
    // Reinicia selección y confirmación si cambian datos del formulario de solicitud.
    //   Desde la vista: <input wire:model.live="solicitud.expediente.paciente.nombres">
    //   Resultado: se descarta cualquier coincidencia potencialmente desactualizada.
    public function updatedSolicitud(): void
    {
        $this->selectedExistingPatientId = null;
        $this->confirmPatientReuse = false;
    }

    /**
     * Selecciona un candidato mostrado y verifica permiso y pertenencia a la lista calculada.
     * @param int $patientId Dato recibido por el componente.
     * @return void Selecciona un candidato mostrado y verifica permiso y pertenencia a la lista calculada.
     */
    // Selecciona un candidato mostrado y verifica permiso y pertenencia a la lista calculada.
    //   Desde la vista: <flux:button wire:click="selectExistingPatient({{ $paciente->id }})">Vincular</flux:button>
    //   Resultado: candidato elegido sin conservar una confirmación anterior.
    public function selectExistingPatient(int $patientId): void
    {
        $this->authorize('requests.edit');

        abort_if(! $this->possiblePatientMatches->contains('id', $patientId), 404);

        $this->selectedExistingPatientId = $patientId;
        $this->confirmPatientReuse = false;
        $this->resetValidation('selectedExistingPatientId');
        $this->resetValidation('confirmPatientReuse');
    }

    /**
     * Valida y vincula la solicitud al expediente del paciente existente seleccionado.
     * @return void Valida y vincula la solicitud al expediente del paciente existente seleccionado.
     */
    // Valida y vincula la solicitud al expediente del paciente existente seleccionado.
    //   Desde la vista: <flux:button wire:click="reuseExistingPatient">Confirmar vínculo</flux:button>
    //   Resultado: cita asociada al expediente y toast con posibles conflictos.
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

    /**
     * Valida y persiste los datos editables del paciente de la solicitud abierta.
     * @return void Valida y persiste los datos editables del paciente de la solicitud abierta.
     */
    // Valida y persiste los datos editables del paciente de la solicitud abierta.
    //   Desde la vista: <flux:button wire:click="store">Guardar paciente</flux:button>
    //   Resultado: detalle actualizado y toast de confirmación.
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

    /**
     * Confirma una cita con asignaciones activas y delega la operación al servicio de gestión.
     * @param int $id Dato recibido por el componente.
     * @return void Confirma una cita con asignaciones activas y delega la operación al servicio de gestión.
     */
    // Confirma una cita con asignaciones activas y delega la operación al servicio de gestión.
    //   Desde la vista: <flux:button wire:click="confirmar({{ $solicitudId }})">Confirmar</flux:button>
    //   Resultado: cita confirmada o aviso si la asignación dejó de estar disponible.
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

    /**
     * Precarga asignaciones válidas y abre el formulario de confirmación para la cita.
     * @param int $id Dato recibido por el componente.
     * @return void Precarga asignaciones válidas y abre el formulario de confirmación para la cita.
     */
    // Precarga asignaciones válidas y abre el formulario de confirmación para la cita.
    //   Desde la vista: <flux:button wire:click="prepararConfirmacion({{ $row->id }})">Asignar</flux:button>
    //   Resultado: confirmar-cita-modal muestra sede, profesional y especialidad sugeridos.
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

    /**
     * Al cambiar sede, limpia opciones dependientes y autoselecciona si solo hay una.
     * @param ?string $sucursalId Dato recibido por el componente.
     * @return void Al cambiar sede, limpia opciones dependientes y autoselecciona si solo hay una.
     */
    // Al cambiar sede, limpia opciones dependientes y autoselecciona si solo hay una.
    //   Desde la vista: <select wire:model.live="sucursalConfirmacion">...</select>
    //   Resultado: se cargan profesionales activos para la sede elegida.
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

    /**
     * Al cambiar profesional, reinicia y resuelve la especialidad única cuando exista.
     * @param ?string $profesionalId Dato recibido por el componente.
     * @return void Al cambiar profesional, reinicia y resuelve la especialidad única cuando exista.
     */
    // Al cambiar profesional, reinicia y resuelve la especialidad única cuando exista.
    //   Desde la vista: <select wire:model.live="profesionalConfirmacion">...</select>
    //   Resultado: especialidad previa limpia o reemplazada por la única válida.
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

    /**
     * Consulta profesionales activos asignados a una sede y aptos para el tipo de cita.
     * @param ?int $sucursalId Dato recibido por el componente.
     * @return \Illuminate\Database\Eloquent\Collection Consulta profesionales activos asignados a una sede y aptos para el tipo de cita.
     */
    // Consulta profesionales activos asignados a una sede y aptos para el tipo de cita.
    //   Ejemplo: $this->profesionalesPorSucursal(3)
    //   Resultado: colección ordenada; en primera cita MBI solo devuelve su evaluador.
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

    /**
     * Completa asignaciones únicas para evitar elecciones manuales cuando no hay ambigüedad.
     * @return void Completa asignaciones únicas para evitar elecciones manuales cuando no hay ambigüedad.
     */
    // Completa asignaciones únicas para evitar elecciones manuales cuando no hay ambigüedad.
    //   Ejemplo: una sede con un solo profesional y una sola especialidad.
    //   Resultado: los campos de confirmación se completan solo con opciones únicas.
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

    /**
     * Consulta especialidades activas asociadas al profesional seleccionado.
     * @param ?int $profesionalId Dato recibido por el componente.
     * @return \Illuminate\Database\Eloquent\Collection Consulta especialidades activas asociadas al profesional seleccionado.
     */
    // Consulta especialidades activas asociadas al profesional seleccionado.
    //   Ejemplo: $this->especialidadesPorProfesional(8)
    //   Resultado: colección ordenada y, para MBI, restringida a Evaluación MBI.
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

    /**
     * Genera una URL firmada de siete días para completar los datos del paciente.
     * @param int $id Dato recibido por el componente.
     * @return void Genera una URL firmada de siete días para completar los datos del paciente.
     */
    // Genera una URL firmada de siete días para completar los datos del paciente.
    //   Desde la vista: <flux:button wire:click="generarLinkFormulario({{ $row->id }})">Enviar formulario</flux:button>
    //   Resultado: modal con el nombre del paciente y el enlace temporal.
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

    /**
     * Verifica que la cita siga pendiente y precarga su fecha y hora actuales.
     * @param int $id Dato recibido por el componente.
     * @return void Verifica que la cita siga pendiente y precarga su fecha y hora actuales.
     */
    // Verifica que la cita siga pendiente y precarga su fecha y hora actuales.
    //   Desde la vista: <flux:button wire:click="prepararReprogramacion({{ $row->id }})">Reprogramar</flux:button>
    //   Resultado: se abre el modal para elegir un horario sustituto.
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

    /**
     * Valida el nuevo horario y solicita al servicio reprogramar la cita pendiente.
     * @return void Valida el nuevo horario y solicita al servicio reprogramar la cita pendiente.
     */
    // Valida el nuevo horario y solicita al servicio reprogramar la cita pendiente.
    //   Desde la vista: <flux:button wire:click="reprogramar">Guardar horario</flux:button>
    //   Resultado: cita pendiente con nuevo horario o error de disponibilidad.
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

    /**
     * Valida que la solicitud siga pendiente y abre el formulario de cancelación.
     * @param int $id Dato recibido por el componente.
     * @return void Valida que la solicitud siga pendiente y abre el formulario de cancelación.
     */
    // Valida que la solicitud siga pendiente y abre el formulario de cancelación.
    //   Desde la vista: <flux:button wire:click="prepararCancelacion({{ $row->id }})">Cancelar</flux:button>
    //   Resultado: modal asociado a la cita y motivo anterior reiniciado.
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

    /**
     * Valida el motivo y pide al servicio cancelar la cita pendiente.
     * @return void Valida el motivo y pide al servicio cancelar la cita pendiente.
     */
    // Valida el motivo y pide al servicio cancelar la cita pendiente.
    //   Desde la vista: <flux:button wire:click="cancelar">Confirmar cancelación</flux:button>
    //   Resultado: cancelación registrada o aviso si cambió el estado.
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

    /**
     * Limpia el estado temporal de la solicitud y cierra los modales abiertos.
     * @return void Limpia el estado temporal de la solicitud y cierra los modales abiertos.
     */
    // Limpia el estado temporal de la solicitud y cierra los modales abiertos.
    //   Ejemplo: invocado al terminar la edición o vinculación del paciente.
    //   Resultado: formulario limpio para abrir otro registro.
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

    /**
     * Recupera la cita seleccionada con expediente y paciente, o responde 404 si falta.
     * @return Cita Recupera la cita seleccionada con expediente y paciente, o responde 404 si falta.
     */
    // Recupera la cita seleccionada con expediente y paciente, o responde 404 si falta.
    //   Ejemplo: $cita = $this->selectedRequest() desde store() o reprogramar().
    //   Resultado: cita actual con relaciones necesarias precargadas.
    private function selectedRequest(): Cita
    {
        abort_if($this->solicitudId < 1, 404);

        return Cita::with(['expediente.paciente'])->findOrFail($this->solicitudId);
    }
}
