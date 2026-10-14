<?php

namespace App\Livewire;

use App\Livewire\Traits\HasCitas;
use App\Models\Cita;
use App\Models\Sucursal;
use App\Services\AppointmentManagementService;
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

    public ?string $formularioUrl = null;

    public ?string $formularioNombre = null;

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
        ]);
    }

    public function showSolicitud(int $id): void
    {
        $this->authorize('page.view.requests');

        $cita = Cita::with(['expediente.paciente'])->findOrFail($id);
        $this->solicitudId = $cita->id;
        $this->solicitud = $cita->toArray();
        Flux::modal('solicitud-modal')->show();
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
        ], [
            'sucursalConfirmacion.required' => 'Selecciona la sede donde se atenderá la cita.',
            'sucursalConfirmacion.exists' => 'La sede seleccionada no está activa. Selecciona otra.',
        ]);
        $sucursalId = (int) $datos['sucursalConfirmacion'];

        $resultado = app(AppointmentManagementService::class)->confirm($id, $sucursalId, Auth::id());

        if ($resultado !== 'confirmada') {
            Flux::toast(
                text: match ($resultado) {
                    'no_pendiente' => 'La solicitud ya no se encuentra pendiente.',
                    'sede_no_disponible' => 'La sede seleccionada dejó de estar activa. Selecciona otra sede.',
                    default => 'El horario ya no está disponible. Reprograma la solicitud antes de confirmarla.',
                },
                variant: 'danger',
            );

            return;
        }

        $this->reset('sucursalConfirmacion');
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
        $this->sucursalConfirmacion = $cita->sucursal_id ? (string) $cita->sucursal_id : null;
        $this->resetValidation('sucursalConfirmacion');
        Flux::modal('confirmar-cita-modal')->show();
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
        $this->reset(['solicitud', 'solicitudId', 'sucursalConfirmacion', 'reprogramacion', 'motivoCancelacion', 'formularioUrl', 'formularioNombre']);
        Flux::modals()->close();
    }

    private function selectedRequest(): Cita
    {
        abort_if($this->solicitudId < 1, 404);

        return Cita::with(['expediente.paciente'])->findOrFail($this->solicitudId);
    }
}
