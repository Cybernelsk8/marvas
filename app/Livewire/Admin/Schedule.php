<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\HasCitas;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Sucursal;
use App\Models\SucursalEvaluadorMbi;
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

/**
 * Componente Livewire que muestra y administra la agenda, sus citas y solicitudes pendientes.
 */
#[Title('Agenda')]
class Schedule extends Component
{
    use HasCitas;

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

    public bool $mbiEvaluatorRequired = false;

    public array $reprogramacion = ['fecha' => null, 'hora' => null];

    public string $motivoCancelacion = '';

    public string $bookingPatientSearch = '';

    public ?string $bookingPatientId = null;

    public string $bookingType = 'primera_vez';

    public ?string $bookingDate = null;

    public ?string $bookingTime = null;

    public ?string $bookingBranchId = null;

    public ?string $bookingProfessionalId = null;

    public ?string $bookingSpecialtyId = null;

    public string $bookingNotes = '';

    /**
     * Livewire lo ejecuta al montar el componente.
     *
     * @return void
     */
    // Livewire lo ejecuta al montar el componente; autoriza el acceso y fija la fecha inicial en hoy.
    //   Ejemplo: al abrir Agenda, `mount()` establece `selectedDate` en hoy.
    //   Resultado: se muestra la agenda del día actual.
    public function mount(): void
    {
        $this->authorize('page.view.schedule');

        $this->selectedDate = today()->toDateString();
    }

    /**
     * Livewire lo invoca al renderizar.
     *
     * @return \Illuminate\Contracts\View\View
     */
    // Livewire lo invoca al renderizar; prepara opciones y datos dependientes de los filtros vigentes.
    //   Ejemplo: `render()` entrega `events`, `statuses` y `bookingHours` a la vista.
    //   Resultado: calendario y formularios reflejan filtros y horarios actuales.
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
            'horariosReprogramacion' => $this->reprogramacion['fecha']
                ? $this->getAvailableHours(
                    $this->reprogramacion['fecha'],
                    ignoreAppointmentId: $this->appointmentActionId,
                )
                : [],
            'bookingPatients' => $this->bookingPatients(),
            'selectedBookingPatient' => $this->bookingPatientId
                ? Paciente::query()
                    ->where('activo', true)
                    ->whereNull('fusionado_en_paciente_id')
                    ->whereHas('expediente')
                    ->find($this->bookingPatientId)
                : null,
            'bookingProfessionals' => $this->bookingProfessionals(),
            'bookingSpecialties' => $this->bookingSpecialties(),
            'bookingHours' => $this->bookingDate
                ? $this->getAvailableHours($this->bookingDate)
                : [],
        ]);
    }

    /**
     * La vista lo llama al iniciar una reserva.
     *
     * @return void
     */
    // La vista lo llama al iniciar una reserva; reinicia campos y propone una fecha futura seleccionada o, en otro caso, hoy.
    //   Ejemplo: `<flux:button wire:click="prepareBooking">Crear cita</flux:button>`.
    //   Resultado: abre el modal limpio con una fecha inicial válida.
    public function prepareBooking(): void
    {
        $this->authorize('appointments.create');
        $this->resetBookingForm();
        $this->bookingDate = Carbon::parse($this->selectedDate)->isFuture()
            ? $this->selectedDate
            : today()->toDateString();
        Flux::modal('agenda-crear-cita-modal')->show();
    }

    /**
     * La búsqueda lo invoca con el ID elegido.
     *
     * @param  int  $id  Identificador del registro.
     *
     * @return void
     */
    // La búsqueda lo invoca con el ID elegido; verifica que el paciente esté activo, no fusionado y tenga expediente.
    //   Ejemplo: `wire:click="selectBookingPatient({{ $patient->id }})"`.
    //   Resultado: el ID y nombre del paciente quedan cargados.
    public function selectBookingPatient(int $id): void
    {
        $this->authorize('appointments.create');
        $patient = Paciente::query()
            ->where('activo', true)
            ->whereNull('fusionado_en_paciente_id')
            ->whereHas('expediente')
            ->findOrFail($id);
        $this->bookingPatientId = (string) $patient->id;
        $this->bookingPatientSearch = $patient->nombre_completo;
    }

    /**
     * La vista lo invoca para permitir otro paciente.
     *
     * @return void
     */
    // La vista lo invoca para permitir otro paciente; borra el ID y el texto de búsqueda.
    //   Ejemplo: `<flux:button wire:click="changeBookingPatient">Cambiar</flux:button>`.
    //   Resultado: la búsqueda queda disponible sin paciente seleccionado.
    public function changeBookingPatient(): void
    {
        $this->authorize('appointments.create');
        $this->bookingPatientId = null;
        $this->bookingPatientSearch = '';
    }

    /**
     * Livewire lo ejecuta al cambiar `bookingType` para no conservar asignaciones que pudieran ser incompatibles.
     *
     * @return void
     */
    // Livewire lo ejecuta al cambiar `bookingType` para no conservar asignaciones que pudieran ser incompatibles.
    //   Ejemplo: `<select wire:model="bookingType">…</select>` cambia a `seguimiento`.
    //   Resultado: profesional y especialidad deben elegirse de nuevo.
    public function updatedBookingType(): void
    {
        $this->authorize('appointments.create');
        $this->bookingProfessionalId = null;
        $this->bookingSpecialtyId = null;
        if ($this->bookingType === 'primera_vez') {
            $this->bookingProfessionalId = null;
        }
    }

    /**
     * Livewire lo ejecuta al cambiar `bookingBranchId` para no conservar datos de otra sede.
     *
     * @return void
     */
    // Livewire lo ejecuta al cambiar `bookingBranchId` para no conservar datos de otra sede.
    //   Ejemplo: `<select wire:model="bookingBranchId">…</select>` elige otra sede.
    //   Resultado: profesional y especialidad quedan sin seleccionar.
    public function updatedBookingBranchId(): void
    {
        $this->authorize('appointments.create');
        $this->bookingProfessionalId = null;
        $this->bookingSpecialtyId = null;
    }

    /**
     * Livewire lo ejecuta al cambiar el profesional, pues sus especialidades dependen de esa selección.
     *
     * @return void
     */
    // Livewire lo ejecuta al cambiar el profesional, pues sus especialidades dependen de esa selección.
    //   Ejemplo: `<select wire:model="bookingProfessionalId">…</select>` elige otro.
    //   Resultado: debe elegirse una especialidad compatible.
    public function updatedBookingProfessionalId(): void
    {
        $this->authorize('appointments.create');
        $this->bookingSpecialtyId = null;
    }

    /**
     * Livewire le pasa la nueva fecha.
     *
     * @param  ?string  $date  Fecha recibida desde Livewire.
     *
     * @return void
     */
    // Livewire le pasa la nueva fecha; valida el formato antes de consultar horarios y borra la hora anterior.
    //   Ejemplo: `<input type="date" wire:model="bookingDate">` recibe `2026-10-25`.
    //   Resultado: se calculan horarios para la fecha nueva y se descarta la hora anterior.
    public function updatedBookingDate(?string $date): void
    {
        $this->authorize('appointments.create');
        $this->bookingTime = null;
        if ($date !== null && $date !== '') {
            validator(['bookingDate' => $date], ['bookingDate' => ['required', 'date_format:Y-m-d']])->validate();
        }
    }

    /**
     * La vista lo llama al enviar el formulario.
     *
     * @return void
     */
    // La vista lo llama al enviar el formulario; valida los datos y delega las reglas de disponibilidad al servicio.
    //   Ejemplo: `<flux:button wire:click="createDirectAppointment">Guardar cita</flux:button>`.
    //   Resultado: si confirma, cierra el modal y notifica éxito; si no, muestra el error.
    public function createDirectAppointment(): void
    {
        $this->authorize('appointments.create');
        $validated = $this->validate([
            'bookingPatientId' => [
                'required',
                'integer',
                Rule::exists('pacientes', 'id')->where('activo', true)->whereNull('fusionado_en_paciente_id'),
            ],
            'bookingType' => ['required', Rule::in(['primera_vez', 'seguimiento', 'control', 'evaluacion_integral', 'procedimiento'])],
            'bookingDate' => ['required', 'date_format:Y-m-d'],
            'bookingTime' => ['required', 'date_format:H:i'],
            'bookingBranchId' => ['required', 'integer', Rule::exists('sucursales', 'id')->where('activa', true)],
            'bookingProfessionalId' => ['required', 'integer', Rule::exists('profesionales', 'id')->where('activo', true)],
            'bookingSpecialtyId' => ['required', 'integer', Rule::exists('especialidades', 'id')->where('activa', true)],
            'bookingNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $start = Carbon::createFromFormat('!Y-m-d H:i', "{$validated['bookingDate']} {$validated['bookingTime']}");
        $result = app(AppointmentManagementService::class)->createDirect(
            (int) $validated['bookingPatientId'],
            (int) $validated['bookingBranchId'],
            (int) Auth::id(),
            (int) $validated['bookingProfessionalId'],
            (int) $validated['bookingSpecialtyId'],
            $validated['bookingType'],
            $start,
            $validated['bookingNotes'] ?: null,
        );

        if ($result !== 'confirmada') {
            $message = match ($result) {
                'paciente_no_disponible' => 'El paciente debe estar activo y contar con expediente.',
                'sede_no_disponible' => 'La sede seleccionada ya no está disponible.',
                'profesional_no_disponible' => 'El profesional no está activo o no atiende en esta sede.',
                'especialidad_no_disponible' => 'La especialidad no está activa o no corresponde al profesional.',
                'evaluador_no_disponible' => 'La sede no tiene un Evaluador MBI disponible para primera visita.',
                'tipo_no_disponible' => 'El tipo de cita seleccionado no está disponible.',
                default => 'El horario seleccionado ya no está disponible.',
            };
            $this->addError('bookingTime', $message);

            return;
        }

        $this->resetBookingForm();
        Flux::modal('agenda-crear-cita-modal')->close();
        unset($this->appointments);
        Flux::toast(text: 'Cita creada y confirmada.', variant: 'success');
    }

    /**
     * Se usa al abrir el modal y tras crear una cita para no reutilizar datos anteriores.
     *
     * @return void
     */
    // Se usa al abrir el modal y tras crear una cita para no reutilizar datos anteriores.
    //   Ejemplo: `$this->resetBookingForm()` después de guardar correctamente.
    //   Resultado: el tipo vuelve a `primera_vez` y los demás campos quedan limpios.
    public function resetBookingForm(): void
    {
        $this->reset([
            'bookingPatientSearch',
            'bookingPatientId',
            'bookingType',
            'bookingDate',
            'bookingTime',
            'bookingBranchId',
            'bookingProfessionalId',
            'bookingSpecialtyId',
            'bookingNotes',
        ]);
        $this->bookingType = 'primera_vez';
        $this->resetValidation();
    }

    /**
     * Se invoca desde `render()`.
     *
     * @return Collection
     */
    // Se invoca desde `render()`; busca solo con al menos dos caracteres y sin un paciente ya seleccionado.
    //   Ejemplo: con `bookingPatientSearch = "Ana"`, se llama `$this->bookingPatients()`.
    //   Resultado: devuelve hasta diez pacientes elegibles o una colección vacía.
    private function bookingPatients(): Collection
    {
        $search = trim($this->bookingPatientSearch);
        if (mb_strlen($search) < 2 || $this->bookingPatientId) {
            return new Collection;
        }

        return Paciente::query()
            ->where('activo', true)
            ->whereNull('fusionado_en_paciente_id')
            ->whereHas('expediente')
            ->where(function (Builder $query) use ($search): void {
                $query->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")
                    ->orWhere('numero_dpi', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('nombres')
            ->limit(10)
            ->get(['id', 'nombres', 'apellidos', 'numero_dpi']);
    }

    /**
     * Se invoca desde `render()` y filtra por sede.
     *
     * @return SupportCollection
     */
    // Se invoca desde `render()` y filtra por sede; para primera visita exige el Evaluador MBI configurado.
    //   Ejemplo: `$this->bookingProfessionals()` con `bookingBranchId = "2"`.
    //   Resultado: devuelve profesionales elegibles ordenados o una colección vacía sin sede.
    private function bookingProfessionals(): SupportCollection
    {
        if (! $this->bookingBranchId) {
            return collect();
        }

        $query = Profesional::query()
            ->where('activo', true)
            ->whereHas('sucursales', fn ($builder) => $builder->whereKey($this->bookingBranchId));

        if ($this->bookingType === 'primera_vez') {
            $evaluatorId = SucursalEvaluadorMbi::query()
                ->where('sucursal_id', $this->bookingBranchId)
                ->value('profesional_id');
            $query->whereKey($evaluatorId ?? 0)
                ->whereHas('especialidades', fn ($builder) => $builder
                    ->where('slug', 'evaluacion-mbi')
                    ->where('activa', true));
        }

        return $query->orderBy('nombre_completo')->get();
    }

    /**
     * Se invoca desde `render()`.
     *
     * @return SupportCollection
     */
    // Se invoca desde `render()`; limita a Evaluación MBI para primeras visitas.
    //   Ejemplo: `$this->bookingSpecialties()` con un profesional seleccionado.
    //   Resultado: devuelve especialidades compatibles o una colección vacía sin profesional.
    private function bookingSpecialties(): SupportCollection
    {
        if (! $this->bookingProfessionalId) {
            return collect();
        }

        return Especialidad::query()
            ->where('activa', true)
            ->when($this->bookingType === 'primera_vez', fn ($query) => $query->where('slug', 'evaluacion-mbi'))
            ->whereHas('profesionales', fn ($query) => $query
                ->whereKey($this->bookingProfessionalId)
                ->where('activo', true))
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Livewire pasa la fecha nueva.
     *
     * @param  ?string  $fecha  Fecha seleccionada para reprogramar.
     *
     * @return void
     */
    // Livewire pasa la fecha nueva; conserva la hora solo si sigue libre, excluyendo la cita que se reprograma.
    //   Ejemplo: `<input type="date" wire:model="reprogramacion.fecha">` cambia de día.
    //   Resultado: se conserva la hora disponible o se limpia para elegir otra.
    public function updatedReprogramacionFecha(?string $fecha): void
    {
        if (! $fecha || ! in_array(
            $this->reprogramacion['hora'] ?? null,
            $this->getAvailableHours($fecha, ignoreAppointmentId: $this->appointmentActionId),
            true,
        )) {
            $this->reprogramacion['hora'] = null;
        }
    }

    /**
     * La vista lo invoca al elegir modo.
     *
     * @param  string  $viewMode  Modo de visualización solicitado.
     *
     * @return void
     */
    // La vista lo invoca al elegir modo; valida el valor e invalida las citas computadas.
    //   Ejemplo: `wire:click="setViewMode('week')"`.
    //   Resultado: la agenda cambia a semana y actualiza sus citas.
    public function setViewMode(string $viewMode): void
    {
        $this->authorize('page.view.schedule');
        validator(['viewMode' => $viewMode], [
            'viewMode' => ['required', Rule::in(['day', 'week', 'month'])],
        ])->validate();

        $this->viewMode = $viewMode;
        unset($this->appointments);
    }

    /**
     * La vista envía -1 o 1 al pulsar las flechas.
     *
     * @param  int  $direction  Dirección de navegación: -1 o 1.
     *
     * @return void
     */
    // La vista envía -1 o 1 al pulsar las flechas; se rechazan otras direcciones.
    //   Ejemplo: `<button wire:click="navigatePeriod(1)">Siguiente</button>`.
    //   Resultado: avanza un día, semana o mes según el modo.
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

    /**
     * La vista lo invoca desde el control Hoy e invalida las citas computadas.
     *
     * @return void
     */
    // La vista lo invoca desde el control Hoy e invalida las citas computadas.
    //   Ejemplo: `<flux:button wire:click="goToToday">Hoy</flux:button>`.
    //   Resultado: `selectedDate` queda en hoy y el calendario vuelve a ese periodo.
    public function goToToday(): void
    {
        $this->authorize('page.view.schedule');
        $this->selectedDate = today()->toDateString();
        unset($this->appointments);
    }

    /**
     * Lo invoca el evento `calendar-day-selected`.
     *
     * @param  string  $date  Fecha recibida desde Livewire.
     *
     * @param  array  $events  Eventos enviados por el calendario.
     *
     * @return void
     */
    // Lo invoca el evento `calendar-day-selected`; valida que la fecha ISO exista antes de actualizar la agenda.
    //   Ejemplo: `<flux:calendar event-name="calendar-day-selected" />` al elegir `2026-10-24`.
    //   Resultado: se actualiza la agenda a esa fecha o se informa una fecha inválida.
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

    /**
     * Livewire lo ejecuta al cambiar `status`.
     *
     * @return void
     */
    // Livewire lo ejecuta al cambiar `status`; solo permite estados conocidos, además de activa y todas.
    //   Ejemplo: `<select wire:model="status">…</select>` cambia a `confirmada`.
    //   Resultado: la agenda muestra únicamente citas confirmadas.
    public function updatedStatus(): void
    {
        $this->authorize('page.view.schedule');
        $this->validateOnly('status', [
            'status' => ['required', Rule::in(['activa', 'todas', ...array_keys($this->statusOptions())])],
        ]);
        unset($this->appointments);
    }

    /**
     * Livewire lo ejecuta al cambiar `sucursalFiltro`.
     *
     * @return void
     */
    // Livewire lo ejecuta al cambiar `sucursalFiltro`; acepta todas, sin sede o una sede existente.
    //   Ejemplo: `<select wire:model="sucursalFiltro">…</select>` elige sede 3.
    //   Resultado: la agenda queda limitada a esa sede.
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

    /**
     * Livewire lo ejecuta al cambiar `profesionalFiltro`.
     *
     * @return void
     */
    // Livewire lo ejecuta al cambiar `profesionalFiltro`; acepta todos, sin asignar o un profesional existente.
    //   Ejemplo: `<select wire:model="profesionalFiltro">…</select>` elige profesional 4.
    //   Resultado: la agenda muestra sus citas asignadas.
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

    /**
     * Livewire lo ejecuta al cambiar `viewMode` directamente y restringe sus valores a día, semana o mes.
     *
     * @return void
     */
    // Livewire lo ejecuta al cambiar `viewMode` directamente y restringe sus valores a día, semana o mes.
    //   Ejemplo: `<select wire:model="viewMode">…</select>` elige `day`.
    //   Resultado: se invalida la caché y queda activo el modo diario.
    public function updatedViewMode(): void
    {
        $this->authorize('page.view.schedule');
        $this->validateOnly('viewMode', ['viewMode' => ['required', Rule::in(['day', 'week', 'month'])]]);
        unset($this->appointments);
    }

    /**
     * Livewire lo ejecuta al cambiar la sede.
     *
     * @param  ?string  $sucursalId  ID de sede seleccionada.
     *
     * @return void
     */
    // Livewire lo ejecuta al cambiar la sede; limpia selecciones dependientes y autoselecciona si hay un solo profesional.
    //   Ejemplo: `<select wire:model="sucursalConfirmacion">…</select>` elige sede 2.
    //   Resultado: aparecen profesionales elegibles y se autoselecciona la única opción.
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

    /**
     * Livewire lo ejecuta al cambiar profesional.
     *
     * @param  ?string  $profesionalId  ID del profesional seleccionado.
     *
     * @return void
     */
    // Livewire lo ejecuta al cambiar profesional; elimina especialidades obsoletas y autoselecciona la única disponible.
    //   Ejemplo: `<select wire:model="profesionalConfirmacion">…</select>` elige profesional 5.
    //   Resultado: se cargan especialidades activas y se elige la única, si existe.
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

    /**
     * La bandeja lo invoca con la cita.
     *
     * @param  int  $id  Identificador del registro.
     *
     * @return void
     */
    // La bandeja lo invoca con la cita; comprueba que siga pendiente o necesite completar su asignación.
    //   Ejemplo: `wire:click="prepareConfirmation({{ $row->id }})"`.
    //   Resultado: abre el modal con asignaciones elegibles; primeras visitas requieren Evaluador MBI.
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
        $this->mbiEvaluatorRequired = $cita->tipo_cita === 'primera_vez';
        $this->sucursalConfirmacion = $cita->sucursal_id ? (string) $cita->sucursal_id : null;
        $this->profesionalConfirmacion = $cita->profesional_id ? (string) $cita->profesional_id : null;
        $this->especialidadConfirmacion = $cita->especialidad_id ? (string) $cita->especialidad_id : null;
        $this->selectOnlyAvailableAssignments();
        $this->resetValidation(['sucursalConfirmacion', 'profesionalConfirmacion', 'especialidadConfirmacion']);
        Flux::modal('agenda-confirmar-cita-modal')->show();
    }

    /**
     * La vista lo invoca al guardar el modal.
     *
     * @return void
     */
    // La vista lo invoca al guardar el modal; el servicio vuelve a validar disponibilidad y reglas del dominio.
    //   Ejemplo: `<flux:button wire:click="confirmAppointment">Confirmar cita</flux:button>`.
    //   Resultado: confirma o asigna la cita y notifica éxito, o presenta el conflicto.
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
                    'evaluador_no_disponible' => 'La sede no tiene configurado ese profesional como Evaluador MBI.',
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
            'mbiEvaluatorRequired',
        ]);
        unset($this->appointments);
        Flux::modals()->close();
        Flux::toast(
            text: $wasAssignment ? 'Se asignaron profesional y especialidad a la cita.' : 'La cita fue confirmada correctamente.',
            variant: 'success',
        );
    }

    /**
     * La bandeja lo invoca para cambiar horario.
     *
     * @param  int  $id  Identificador del registro.
     *
     * @return void
     */
    // La bandeja lo invoca para cambiar horario; solo acepta solicitudes en estado `solicitada`.
    //   Ejemplo: `wire:click="prepareRescheduling({{ $row->id }})"`.
    //   Resultado: el modal muestra fecha y hora actuales para seleccionar otras.
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

    /**
     * La vista lo invoca al guardar.
     *
     * @return void
     */
    // La vista lo invoca al guardar; el servicio comprueba que siga pendiente y que el horario esté libre.
    //   Ejemplo: `<flux:button wire:click="rescheduleAppointment">Guardar horario</flux:button>`.
    //   Resultado: la solicitud cambia de horario y continúa pendiente, o se muestra el error.
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

    /**
     * La bandeja lo invoca antes de pedir el motivo.
     *
     * @param  int  $id  Identificador del registro.
     *
     * @return void
     */
    // La bandeja lo invoca antes de pedir el motivo; solo permite solicitudes `solicitada`.
    //   Ejemplo: `wire:click="prepareCancellation({{ $row->id }})"`.
    //   Resultado: se abre el modal con el motivo vacío y sin errores previos.
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

    /**
     * La vista lo invoca al confirmar.
     *
     * @return void
     */
    // La vista lo invoca al confirmar; exige un motivo y delega el cambio de estado al servicio.
    //   Ejemplo: `<flux:button wire:click="cancelAppointment">Confirmar cancelación</flux:button>`.
    //   Resultado: se cancela y notifica éxito, o se informa que ya no está pendiente.
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

    /**
     * Se usa para preparar o renderizar confirmaciones.
     *
     * @param  ?int  $sucursalId  ID de sede seleccionada.
     *
     * @return SupportCollection
     */
    // Se usa para preparar o renderizar confirmaciones; en primeras visitas filtra el Evaluador MBI de la sede.
    //   Ejemplo: `$this->profesionalesPorSucursal(2)`.
    //   Resultado: devuelve profesionales elegibles de esa sede o colección vacía.
    public function profesionalesPorSucursal(?int $sucursalId): SupportCollection
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
     * Se usa para mostrar opciones y completar asignaciones.
     *
     * @param  ?int  $profesionalId  ID del profesional seleccionado.
     *
     * @return SupportCollection
     */
    // Se usa para mostrar opciones y completar asignaciones; restringe a Evaluación MBI si la cita lo requiere.
    //   Ejemplo: `$this->especialidadesPorProfesional(5)`.
    //   Resultado: devuelve especialidades activas y compatibles ordenadas.
    public function especialidadesPorProfesional(?int $profesionalId): SupportCollection
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
     * Se invoca al preparar confirmación para completar selecciones únicas sin escoger arbitrariamente entre varias.
     *
     * @return void
     */
    // Se invoca al preparar confirmación para completar selecciones únicas sin escoger arbitrariamente entre varias.
    //   Ejemplo: `$this->selectOnlyAvailableAssignments()` con una cita incompleta.
    //   Resultado: rellena opciones únicas; para primera visita elige evaluador y especialidad MBI.
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
     * Livewire la calcula como propiedad computada al acceder a `$this->appointments`.
     *
     * @return Collection
     */
    // Livewire la calcula como propiedad computada al acceder a `$this->appointments`; el rango depende del modo.
    //   Ejemplo: `{{ $this->appointments->count() }}` en la vista.
    //   Resultado: devuelve las citas del día o semana seleccionados.
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

    /**
     * La usan las consultas de agenda para reutilizar la carga de relaciones y filtros vigentes.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    // La usan las consultas de agenda para reutilizar la carga de relaciones y filtros vigentes.
    //   Ejemplo: `$this->appointmentsQuery()->count()`.
    //   Resultado: queda lista una consulta con paciente, profesional, sede y especialidad.
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

    /**
     * La usan la lista del periodo y los eventos para que ambas vistas respeten los mismos filtros.
     *
     * @param  Builder  $query  Consulta Eloquent que se filtrará.
     *
     * @return Builder
     */
    // La usan la lista del periodo y los eventos para que ambas vistas respeten los mismos filtros.
    //   Ejemplo: `$this->applyFilters(Cita::query())`.
    //   Resultado: devuelve la consulta con los criterios seleccionados encadenados.
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

    /**
     * Se invoca desde `render()`.
     *
     * @return array
     */
    // Se invoca desde `render()`; prepara la fecha seleccionada o cada día de la semana.
    //   Ejemplo: `$this->appointmentDays()` en modo semanal.
    //   Resultado: devuelve un arreglo por fecha ISO con las citas de cada día.
    private function appointmentDays(): array
    {
        $dates = $this->viewMode === 'week'
            ? collect(range(0, 6))->map(fn (int $day) => Carbon::parse($this->selectedDate)->startOfWeek()->addDays($day)->toDateString())
            : collect([$this->selectedDate]);
        $appointmentsByDate = $this->appointments->groupBy(fn (Cita $cita) => $cita->fecha_hora_inicio->toDateString());

        return $dates->mapWithKeys(fn (string $date) => [$date => $appointmentsByDate->get($date, collect())])->all();
    }

    /**
     * Se invoca desde `render()`.
     *
     * @return array
     */
    // Se invoca desde `render()`; limita el rango para dar contexto sin cargar toda la historia.
    //   Ejemplo: `events = $this->events()` al construir la vista.
    //   Resultado: devuelve eventos por fecha con título, hora y color.
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

    /**
     * Se usa para poblar el filtro y traducir el estado de cada evento.
     *
     * @return array
     */
    // Se usa para poblar el filtro y traducir el estado de cada evento.
    //   Ejemplo: `$this->statusOptions()['confirmada']`.
    //   Resultado: devuelve `Confirmada`.
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

    /**
     * La invoca `events()` para distinguir estados.
     *
     * @param  string  $status  Clave del estado de la cita.
     *
     * @return string
     */
    // La invoca `events()` para distinguir estados; usa un color neutro para claves desconocidas.
    //   Ejemplo: `$this->statusColor('confirmada')`.
    //   Resultado: devuelve `#16a34a`.
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
