<?php

namespace App\Livewire\Admin;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Sucursal;
use App\Services\AppointmentManagementService;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Agenda')]
class Schedule extends Component
{
    public string $selectedDate;

    public string $status = 'activa';

    public string $sucursalFiltro = 'todas';

    public string $profesionalFiltro = 'todos';

    public string $viewMode = 'month';

    public int $appointmentActionId = 0;

    public ?string $sucursalConfirmacion = null;

    public ?string $profesionalConfirmacion = null;

    public ?string $especialidadConfirmacion = null;

    public bool $assigningConfirmedAppointment = false;

    public array $reprogramacion = ['fecha' => null, 'hora' => null];

    public string $motivoCancelacion = '';

    public function mount(): void
    {
        $this->authorize('page.view.schedule');

        $this->selectedDate = today()->toDateString();
    }

    public function render()
    {
        return view('livewire.admin.schedule', [
            'events' => $this->events(),
            'statuses' => $this->statusOptions(),
            'sucursales' => Sucursal::query()->orderBy('nombre')->get(['id', 'nombre', 'activa']),
            'profesionalesFiltro' => Profesional::query()->orderBy('nombre_completo')->get(['id', 'nombre_completo', 'activo']),
            'profesionales' => $this->profesionalesPorSucursal((int) ($this->sucursalConfirmacion ?? 0)),
            'especialidadesConfirmacion' => $this->especialidadesPorProfesional((int) ($this->profesionalConfirmacion ?? 0)),
            'appointmentDays' => $this->appointmentDays(),
        ]);
    }

    public function setViewMode(string $viewMode): void
    {
        $this->authorize('page.view.schedule');
        validator(['viewMode' => $viewMode], [
            'viewMode' => ['required', Rule::in(['day', 'week', 'month'])],
        ])->validate();

        $this->viewMode = $viewMode;
        unset($this->appointments);
    }

    public function navigatePeriod(int $direction): void
    {
        $this->authorize('page.view.schedule');

        if (! in_array($direction, [-1, 1], true)) {
            abort(422, 'La dirección de navegación no es válida.');
        }

        $date = Carbon::parse($this->selectedDate);
        $this->selectedDate = match ($this->viewMode) {
            'day' => $date->addDays($direction)->toDateString(),
            'week' => $date->addWeeks($direction)->toDateString(),
            default => $date->addMonthsNoOverflow($direction)->toDateString(),
        };
        unset($this->appointments);
    }

    public function goToToday(): void
    {
        $this->authorize('page.view.schedule');
        $this->selectedDate = today()->toDateString();
        unset($this->appointments);
    }

    #[On('calendar-day-selected')]
    public function selectDate(string $date, array $events = []): void
    {
        $this->authorize('page.view.schedule');

        try {
            $parsed = Carbon::createFromFormat('!Y-m-d', $date);
        } catch (InvalidFormatException) {
            $parsed = null;
        }

        if (! $parsed || $parsed->toDateString() !== $date) {
            $this->addError('selectedDate', 'Selecciona una fecha válida.');

            return;
        }

        $this->selectedDate = $date;
        unset($this->appointments);
    }

    public function updatedStatus(): void
    {
        $this->authorize('page.view.schedule');
        $this->validateOnly('status', [
            'status' => ['required', Rule::in(['activa', 'todas', ...array_keys($this->statusOptions())])],
        ]);
        unset($this->appointments);
    }

    public function updatedSucursalFiltro(): void
    {
        $this->authorize('page.view.schedule');
        $this->validateOnly('sucursalFiltro', [
            'sucursalFiltro' => [
                'required',
                Rule::in(['todas', 'sin_sede', ...Sucursal::query()->pluck('id')->map(fn (int $id) => (string) $id)->all()]),
            ],
        ]);
        unset($this->appointments);
    }

    public function updatedProfesionalFiltro(): void
    {
        $this->authorize('page.view.schedule');
        $this->validateOnly('profesionalFiltro', [
            'profesionalFiltro' => [
                'required',
                Rule::in([
                    'todos',
                    'sin_asignar',
                    ...Profesional::query()->pluck('id')->map(fn (int $id) => (string) $id)->all(),
                ]),
            ],
        ]);
        unset($this->appointments);
    }

    public function updatedViewMode(): void
    {
        $this->authorize('page.view.schedule');
        $this->validateOnly('viewMode', ['viewMode' => ['required', Rule::in(['day', 'week', 'month'])]]);
        unset($this->appointments);
    }

    public function updatedSucursalConfirmacion(?string $sucursalId): void
    {
        $this->profesionalConfirmacion = null;
        $this->especialidadConfirmacion = null;
        $this->resetValidation(['profesionalConfirmacion', 'especialidadConfirmacion']);

        if ($sucursalId === null || $sucursalId === '') {
            return;
        }

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

    public function updatedProfesionalConfirmacion(?string $profesionalId): void
    {
        $this->especialidadConfirmacion = null;
        $this->resetValidation('especialidadConfirmacion');

        if ($profesionalId === null || $profesionalId === '') {
            return;
        }

        $this->validateOnly('profesionalConfirmacion', [
            'profesionalConfirmacion' => [
                'required',
                'integer',
                Rule::exists('profesionales', 'id')->where('activo', true),
            ],
        ]);

        $specialties = $this->especialidadesPorProfesional((int) $profesionalId);

        if ($specialties->count() === 1) {
            $this->especialidadConfirmacion = (string) $specialties->first()->id;
        }
    }

    public function prepareConfirmation(int $id): void
    {
        $this->authorize('requests.confirm');

        $cita = Cita::query()->findOrFail($id);

        $isPending = in_array($cita->estado, ['solicitada', 'reprogramada', 'agendada'], true);
        $hasIncompleteAssignment = ! $cita->sucursal_id || ! $cita->profesional_id || ! $cita->especialidad_id;

        if (! $isPending && ! ($cita->estado === 'confirmada' && $hasIncompleteAssignment)) {
            Flux::toast(text: 'La solicitud ya no se encuentra pendiente.', variant: 'danger');

            return;
        }

        $this->appointmentActionId = $cita->id;
        $this->assigningConfirmedAppointment = ! $isPending;
        $this->sucursalConfirmacion = $cita->sucursal_id ? (string) $cita->sucursal_id : null;
        $this->profesionalConfirmacion = $cita->profesional_id ? (string) $cita->profesional_id : null;
        $this->especialidadConfirmacion = $cita->especialidad_id ? (string) $cita->especialidad_id : null;
        $this->selectOnlyAvailableAssignments();
        $this->resetValidation(['sucursalConfirmacion', 'profesionalConfirmacion', 'especialidadConfirmacion']);
        Flux::modal('agenda-confirmar-cita-modal')->show();
    }

    public function confirmAppointment(): void
    {
        $this->authorize('requests.confirm');

        $data = $this->validate([
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
                        ->whereKey($value)
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

        $management = app(AppointmentManagementService::class);
        $result = $this->assigningConfirmedAppointment
            ? $management->assignConfirmedAppointment(
                $this->appointmentActionId,
                (int) $data['sucursalConfirmacion'],
                Auth::id(),
                (int) $data['profesionalConfirmacion'],
                (int) $data['especialidadConfirmacion'],
            )
            : $management->confirm(
                $this->appointmentActionId,
                (int) $data['sucursalConfirmacion'],
                Auth::id(),
                (int) $data['profesionalConfirmacion'],
                (int) $data['especialidadConfirmacion'],
            );

        if (! in_array($result, ['confirmada', 'asignada'], true)) {
            Flux::toast(
                text: match ($result) {
                    'no_pendiente' => 'La solicitud ya no se encuentra pendiente.',
                    'no_asignable' => 'La cita cambió o ya tiene completa su asignación. Actualiza la agenda.',
                    'sede_no_disponible' => 'La sede seleccionada dejó de estar activa. Selecciona otra sede.',
                    'profesional_no_disponible' => 'El profesional seleccionado no está disponible para esa sede.',
                    'especialidad_no_disponible' => 'La especialidad ya no está activa o no corresponde al profesional.',
                    default => 'El horario ya no está disponible. Reprograma la solicitud antes de confirmarla.',
                },
                variant: 'danger',
            );

            return;
        }

        $wasAssignment = $this->assigningConfirmedAppointment;
        $this->reset([
            'appointmentActionId',
            'sucursalConfirmacion',
            'profesionalConfirmacion',
            'especialidadConfirmacion',
            'assigningConfirmedAppointment',
        ]);
        unset($this->appointments);
        Flux::modals()->close();
        Flux::toast(
            text: $wasAssignment ? 'Se asignaron profesional y especialidad a la cita.' : 'La cita fue confirmada correctamente.',
            variant: 'success',
        );
    }

    public function prepareRescheduling(int $id): void
    {
        $this->authorize('requests.reschedule');

        $cita = Cita::query()->findOrFail($id);

        if ($cita->estado !== 'solicitada') {
            Flux::toast(text: 'Solo se pueden reprogramar solicitudes pendientes.', variant: 'danger');

            return;
        }

        $this->appointmentActionId = $cita->id;
        $this->reprogramacion = [
            'fecha' => $cita->fecha_hora_inicio->toDateString(),
            'hora' => $cita->fecha_hora_inicio->format('H:i'),
        ];
        $this->resetValidation('reprogramacion');
        Flux::modal('agenda-reprogramar-cita-modal')->show();
    }

    public function rescheduleAppointment(): void
    {
        $this->authorize('requests.reschedule');

        $data = $this->validate([
            'reprogramacion.fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reprogramacion.hora' => ['required', 'date_format:H:i'],
        ]);
        $inicio = Carbon::createFromFormat(
            '!Y-m-d H:i',
            $data['reprogramacion']['fecha'].' '.$data['reprogramacion']['hora'],
        );
        $result = app(AppointmentManagementService::class)->reschedule($this->appointmentActionId, $inicio);

        if ($result !== 'reprogramada') {
            $this->addError(
                'reprogramacion.hora',
                match ($result) {
                    'sin_configuracion' => 'No se puede reprogramar: falta configurar los horarios de atención.',
                    'no_pendiente' => 'La solicitud ya no se encuentra pendiente.',
                    default => 'El horario seleccionado ya no está disponible. Elige otro.',
                },
            );

            return;
        }

        $this->reset(['appointmentActionId', 'reprogramacion']);
        unset($this->appointments);
        Flux::modals()->close();
        Flux::toast(text: 'La solicitud fue reprogramada y sigue pendiente de confirmación.', variant: 'success');
    }

    public function prepareCancellation(int $id): void
    {
        $this->authorize('requests.cancel');

        $cita = Cita::query()->findOrFail($id);

        if ($cita->estado !== 'solicitada') {
            Flux::toast(text: 'Solo se pueden cancelar solicitudes pendientes desde esta bandeja.', variant: 'danger');

            return;
        }

        $this->appointmentActionId = $cita->id;
        $this->motivoCancelacion = '';
        $this->resetValidation('motivoCancelacion');
        Flux::modal('agenda-cancelar-cita-modal')->show();
    }

    public function cancelAppointment(): void
    {
        $this->authorize('requests.cancel');

        $data = $this->validate([
            'motivoCancelacion' => ['required', 'string', 'max:2000'],
        ]);
        $result = app(AppointmentManagementService::class)->cancel(
            $this->appointmentActionId,
            $data['motivoCancelacion'],
        );

        if ($result !== 'cancelada') {
            $this->addError('motivoCancelacion', 'La solicitud ya no se encuentra pendiente.');

            return;
        }

        $this->reset(['appointmentActionId', 'motivoCancelacion']);
        unset($this->appointments);
        Flux::modals()->close();
        Flux::toast(text: 'La solicitud fue cancelada.', variant: 'success');
    }

    public function profesionalesPorSucursal(?int $sucursalId): SupportCollection
    {
        if (! $sucursalId) {
            return collect();
        }

        return Profesional::query()
            ->where('activo', true)
            ->whereHas('sucursales', fn ($query) => $query->whereKey($sucursalId))
            ->orderBy('nombre_completo')
            ->get();
    }

    public function especialidadesPorProfesional(?int $profesionalId): SupportCollection
    {
        if (! $profesionalId) {
            return collect();
        }

        return Especialidad::query()
            ->where('activa', true)
            ->whereHas('profesionales', fn ($query) => $query->whereKey($profesionalId)->where('activo', true))
            ->orderBy('nombre')
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

    #[Computed]
    public function appointments(): Collection
    {
        $this->authorize('page.view.schedule');

        $start = Carbon::parse($this->selectedDate)->startOfDay();
        $end = $start->copy()->endOfDay();

        if ($this->viewMode === 'week') {
            $start->startOfWeek();
            $end = $start->copy()->endOfWeek();
        }

        return $this->appointmentsQuery()
            ->whereBetween('fecha_hora_inicio', [$start, $end])
            ->orderBy('fecha_hora_inicio')
            ->get();
    }

    private function appointmentsQuery()
    {
        return $this->applyFilters(Cita::query()
            ->with([
                'expediente.paciente',
                'profesional',
                'sucursal',
                'especialidad',
            ]));
    }

    private function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->status === 'activa', fn (Builder $query) => $query->whereNotIn('estado', ['cancelada', 'no_asistio']))
            ->when($this->status !== 'activa' && $this->status !== 'todas', fn (Builder $query) => $query->where('estado', $this->status))
            ->when($this->sucursalFiltro === 'sin_sede', fn (Builder $query) => $query->whereNull('sucursal_id'))
            ->when(
                ! in_array($this->sucursalFiltro, ['todas', 'sin_sede'], true),
                fn (Builder $query) => $query->where('sucursal_id', $this->sucursalFiltro),
            )
            ->when($this->profesionalFiltro === 'sin_asignar', fn (Builder $query) => $query->whereNull('profesional_id'))
            ->when(
                ! in_array($this->profesionalFiltro, ['todos', 'sin_asignar'], true),
                fn (Builder $query) => $query->where('profesional_id', $this->profesionalFiltro),
            );
    }

    private function appointmentDays(): array
    {
        $dates = $this->viewMode === 'week'
            ? collect(range(0, 6))->map(fn (int $day) => Carbon::parse($this->selectedDate)->startOfWeek()->addDays($day)->toDateString())
            : collect([$this->selectedDate]);
        $appointmentsByDate = $this->appointments->groupBy(fn (Cita $cita) => $cita->fecha_hora_inicio->toDateString());

        return $dates->mapWithKeys(fn (string $date) => [$date => $appointmentsByDate->get($date, collect())])->all();
    }

    private function events(): array
    {
        $desde = today()->subMonths(3)->startOfMonth();
        $hasta = today()->addMonths(6)->endOfMonth();

        return $this->applyFilters(Cita::query())
            ->whereBetween('fecha_hora_inicio', [$desde, $hasta])
            ->orderBy('fecha_hora_inicio')
            ->get(['fecha_hora_inicio', 'estado'])
            ->groupBy(fn (Cita $cita) => $cita->fecha_hora_inicio->toDateString())
            ->map(fn (Collection $citas) => $citas->map(fn (Cita $cita) => [
                'title' => $this->statusOptions()[$cita->estado] ?? $cita->estado,
                'time' => $cita->fecha_hora_inicio->format('H:i'),
                'color' => $this->statusColor($cita->estado),
            ])->values()->all())
            ->all();
    }

    private function statusOptions(): array
    {
        return [
            'solicitada' => 'Solicitada',
            'agendada' => 'Agendada',
            'confirmada' => 'Confirmada',
            'en_atencion' => 'En atención',
            'finalizada' => 'Finalizada',
            'cancelada' => 'Cancelada',
            'reprogramada' => 'Reprogramada',
            'no_asistio' => 'No asistió',
        ];
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'solicitada' => '#d97706',
            'agendada' => '#2563eb',
            'confirmada' => '#16a34a',
            'en_atencion' => '#7c3aed',
            'finalizada' => '#52525b',
            'cancelada', 'no_asistio' => '#dc2626',
            'reprogramada' => '#0891b2',
            default => '#71717a',
        };
    }
}
