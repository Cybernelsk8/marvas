<?php

namespace App\Livewire\Admin;

use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Sucursal;
use App\Models\SucursalEvaluadorMbi;
use App\Services\ClinicAvailabilityService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Componente Livewire para consultar y mantener horarios, cierres y evaluadores MBI por sede.
 */
#[Title('Disponibilidad')]
class Availability extends Component
{
    public array $groups = [];

    public int $durationMinutes = 60;

    public array $closures = [];

    public array $conflicts = [];

    public array $evaluadoresMbi = [];

    /**
     * Livewire lo ejecuta al montar la pantalla.
     *
     * @return void
     */
    // Livewire lo ejecuta al montar la pantalla; carga la configuración guardada y prepara los selectores.
    //   Ejemplo: al abrir Disponibilidad, `mount()` carga grupos, duración, cierres y evaluadores.
    //   Resultado: el formulario muestra los datos existentes.
    public function mount(): void
    {
        $this->authorize('page.view.schedule');

        $configuration = app(ClinicAvailabilityService::class)->configuration();
        $this->groups = array_values($configuration['groups']);
        $this->durationMinutes = $configuration['duration_minutes'];
        $this->closures = array_values($configuration['closures']);
        $this->evaluadoresMbi = Sucursal::query()
            ->where('activa', true)
            ->orderBy('id')
            ->get(['id'])
            ->mapWithKeys(fn (Sucursal $branch): array => [
                (string) $branch->id => $branch->evaluadorMbi()->value('profesional_id'),
            ])
            ->all();
    }

    /**
     * La vista lo invoca al agregar un grupo.
     *
     * @return void
     */
    // La vista lo invoca al agregar un grupo; el límite de siete corresponde a los días de la semana.
    //   Ejemplo: `<flux:button wire:click="addGroup">Agregar grupo</flux:button>`.
    //   Resultado: aparece un grupo vacío, hasta un máximo de siete.
    public function addGroup(): void
    {
        $this->authorize('schedule.edit');

        if (count($this->groups) < 7) {
            $this->groups[] = ['days' => [], 'hours' => []];
        }
    }

    /**
     * La vista envía el índice de la fila.
     *
     * @param  int  $index  Índice del elemento que se modificará.
     *
     * @return void
     */
    // La vista envía el índice de la fila; reindexar conserva índices contiguos para los enlaces Livewire.
    //   Ejemplo: `wire:click="removeGroup({{ $index }})"`.
    //   Resultado: desaparece el grupo indicado y los demás conservan su orden.
    public function removeGroup(int $index): void
    {
        $this->authorize('schedule.edit');

        if (array_key_exists($index, $this->groups)) {
            unset($this->groups[$index]);
            $this->groups = array_values($this->groups);
        }
    }

    /**
     * La vista pasa el índice del grupo para mantener separadas sus franjas.
     *
     * @param  int  $groupIndex  Índice del grupo horario.
     *
     * @return void
     */
    // La vista pasa el índice del grupo para mantener separadas sus franjas.
    //   Ejemplo: `wire:click="addHour({{ $groupIndex }})"`.
    //   Resultado: se añade un campo vacío al grupo existente.
    public function addHour(int $groupIndex): void
    {
        $this->authorize('schedule.edit');

        if (isset($this->groups[$groupIndex])) {
            $this->groups[$groupIndex]['hours'][] = '';
        }
    }

    /**
     * La vista pasa ambos índices para borrar solo esa franja y evitar huecos en el formulario.
     *
     * @param  int  $groupIndex  Índice del grupo horario.
     *
     * @param  int  $hourIndex  Índice de la hora dentro del grupo.
     *
     * @return void
     */
    // La vista pasa ambos índices para borrar solo esa franja y evitar huecos en el formulario.
    //   Ejemplo: `wire:click="removeHour({{ $groupIndex }}, {{ $hourIndex }})"`.
    //   Resultado: se quita esa hora y las demás quedan reindexadas.
    public function removeHour(int $groupIndex, int $hourIndex): void
    {
        $this->authorize('schedule.edit');

        if (isset($this->groups[$groupIndex]['hours'][$hourIndex])) {
            unset($this->groups[$groupIndex]['hours'][$hourIndex]);
            $this->groups[$groupIndex]['hours'] = array_values($this->groups[$groupIndex]['hours']);
        }
    }

    /**
     * Livewire lo ejecuta al renderizar.
     *
     * @return \Illuminate\Contracts\View\View
     */
    // Livewire lo ejecuta al renderizar; lista sedes activas y profesionales activos con especialidad MBI activa.
    //   Ejemplo: `render()` pasa `dayOptions` y `sucursalesEvaluadoresMbi` a la vista.
    //   Resultado: el formulario muestra días y opciones válidas por sede.
    public function render()
    {
        return view('livewire.admin.availability', [
            'dayOptions' => [
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miércoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sábado',
                7 => 'Domingo',
            ],
            'sucursalesEvaluadoresMbi' => Sucursal::query()
                ->where('activa', true)
                ->orderBy('nombre')
                ->get()
                ->map(fn (Sucursal $branch): array => [
                    'sucursal' => $branch,
                    'profesionales' => $branch->profesionales()
                        ->where('profesionales.activo', true)
                        ->whereHas('especialidades', fn ($query) => $query
                            ->where('especialidades.slug', 'evaluacion-mbi')
                            ->where('especialidades.activa', true))
                        ->orderBy('nombre_completo')
                        ->get(['profesionales.id', 'profesionales.nombre_completo']),
                ]),
        ]);
    }

    /**
     * La vista lo invoca para agregar un periodo sin atención.
     *
     * @return void
     */
    // La vista lo invoca para agregar un periodo sin atención; el motivo puede dejarse vacío.
    //   Ejemplo: `<flux:button wire:click="addClosure">Agregar cierre</flux:button>`.
    //   Resultado: aparece una fila para fechas de inicio, fin y motivo.
    public function addClosure(): void
    {
        $this->authorize('schedule.edit');

        $this->closures[] = [
            'start_date' => '',
            'end_date' => '',
            'reason' => '',
        ];
    }

    /**
     * La vista envía el índice de la fila.
     *
     * @param  int  $index  Índice del elemento que se modificará.
     *
     * @return void
     */
    // La vista envía el índice de la fila; reindexar conserva enlaces Livewire coherentes.
    //   Ejemplo: `wire:click="removeClosure({{ $index }})"`.
    //   Resultado: desaparece el cierre elegido y los demás siguen ordenados.
    public function removeClosure(int $index): void
    {
        $this->authorize('schedule.edit');

        if (array_key_exists($index, $this->closures)) {
            unset($this->closures[$index]);
            $this->closures = array_values($this->closures);
        }
    }

    /**
     * La vista lo invoca al guardar.
     *
     * @return void
     */
    // La vista lo invoca al guardar; valida reglas y conflictos con citas futuras antes de persistir.
    //   Ejemplo: `<flux:button wire:click="save">Guardar disponibilidad</flux:button>`.
    //   Resultado: se guardan los cambios o se informan conflictos sin persistirlos.
    public function save(): void
    {
        $this->authorize('schedule.edit');

        $validated = $this->validate([
            'groups' => ['array', 'max:7'],
            'groups.*.days' => ['required', 'array', 'min:1'],
            'groups.*.days.*' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5, 6, 7])],
            'groups.*.hours' => ['required', 'array', 'max:24'],
            'groups.*.hours.*' => ['required', 'date_format:H:i'],
            'durationMinutes' => [
                'required',
                'integer',
                'min:'.ClinicAvailabilityService::MIN_DURATION_MINUTES,
                'max:'.ClinicAvailabilityService::MAX_DURATION_MINUTES,
                'multiple_of:15',
            ],
            'closures' => ['array', 'max:100'],
            'closures.*.start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'closures.*.end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'closures.*.reason' => ['nullable', 'string', 'max:255'],
            'evaluadoresMbi' => ['required', 'array'],
            'evaluadoresMbi.*' => ['nullable', 'integer', 'exists:profesionales,id'],
        ]);

        $activeBranchIds = Sucursal::query()
            ->where('activa', true)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();
        $mappingBranchIds = array_map('strval', array_keys($validated['evaluadoresMbi']));
        sort($activeBranchIds);
        sort($mappingBranchIds);

        if ($activeBranchIds !== $mappingBranchIds) {
            $this->addError('evaluadoresMbi', 'La lista de sedes activas cambió. Actualiza la pantalla e inténtalo nuevamente.');

            return;
        }

        $mbiSpecialty = Especialidad::query()
            ->where('slug', 'evaluacion-mbi')
            ->where('activa', true)
            ->first();

        if (! $mbiSpecialty) {
            $this->addError('evaluadoresMbi', 'No existe una especialidad activa de Evaluación MBI.');

            return;
        }

        foreach ($validated['evaluadoresMbi'] as $branchId => $professionalId) {
            if ($professionalId === null || $professionalId === '') {
                $validated['evaluadoresMbi'][$branchId] = null;

                continue;
            }

            $eligible = Profesional::query()
                ->whereKey((int) $professionalId)
                ->where('activo', true)
                ->whereHas('sucursales', fn ($query) => $query->whereKey((int) $branchId))
                ->whereHas('especialidades', fn ($query) => $query->whereKey($mbiSpecialty->id))
                ->exists();

            if (! $eligible) {
                $this->addError(
                    "evaluadoresMbi.{$branchId}",
                    'Selecciona un profesional activo de esta sede con especialidad Evaluación MBI.',
                );

                return;
            }
        }

        $assignedDays = [];
        foreach ($validated['groups'] as $groupIndex => $group) {
            foreach ($group['days'] as $day) {
                $day = (int) $day;
                if (in_array($day, $assignedDays, true)) {
                    $this->addError("groups.{$groupIndex}.days", 'Cada día solo puede pertenecer a un grupo.');

                    return;
                }
                $assignedDays[] = $day;
            }

            $hours = array_values($group['hours']);
            if (count($hours) !== count(array_unique($hours))) {
                $this->addError("groups.{$groupIndex}.hours", 'No repitas una hora dentro del mismo grupo.');

                return;
            }

            sort($hours);
            for ($index = 1; $index < count($hours); $index++) {
                if (Carbon::createFromFormat('!H:i', $hours[$index - 1])->addMinutes($validated['durationMinutes'])
                    ->gt(Carbon::createFromFormat('!H:i', $hours[$index]))) {
                    $this->addError(
                        "groups.{$groupIndex}.hours",
                        'Los horarios no pueden traslaparse: cada cita dura '.$validated['durationMinutes'].' minutos.',
                    );

                    return;
                }
            }

            foreach ($hours as $hour) {
                if (Carbon::createFromFormat('!H:i', $hour)->addMinutes($validated['durationMinutes'])->format('Y-m-d') !== '1970-01-01') {
                    $this->addError(
                        "groups.{$groupIndex}.hours",
                        'Una cita debe terminar el mismo día con la duración configurada.',
                    );

                    return;
                }
            }
        }

        foreach ($validated['closures'] as $index => $closure) {
            if ($closure['end_date'] < $closure['start_date']) {
                $this->addError("closures.{$index}.end_date", 'El final del cierre no puede ser anterior al inicio.');

                return;
            }
        }

        $configuration = [
            'groups' => array_values(array_map(fn (array $group) => [
                'days' => array_values(array_map('intval', $group['days'])),
                'hours' => collect($group['hours'])->sort()->values()->all(),
            ], $validated['groups'])),
            'duration_minutes' => (int) $validated['durationMinutes'],
            'closures' => array_values(array_map(fn (array $closure) => [
                'start_date' => $closure['start_date'],
                'end_date' => $closure['end_date'],
                'reason' => trim($closure['reason'] ?? ''),
            ], $validated['closures'])),
        ];

        $this->conflicts = [];
        $result = DB::transaction(function () use ($configuration, $validated): array {
            $setting = app(ClinicAvailabilityService::class)->lockSetting();
            $conflicts = app(ClinicAvailabilityService::class)->appointmentsConflictingWith($configuration);

            if ($conflicts->isNotEmpty()) {
                return [
                    'saved' => false,
                    'conflicts' => $conflicts->map(fn ($appointment) => [
                        'id' => $appointment->id,
                        'patient' => $appointment->expediente?->paciente?->nombre_completo ?? 'Paciente',
                        'start' => $appointment->fecha_hora_inicio->format('d/m/Y H:i'),
                    ])->all(),
                ];
            }

            $setting->fill([
                'type' => 'array',
                'value' => $configuration,
                'description' => 'Horarios globales agrupados por días y cierres para las reservas de la clínica.',
            ])->save();

            foreach ($validated['evaluadoresMbi'] as $branchId => $professionalId) {
                if ($professionalId === null) {
                    SucursalEvaluadorMbi::query()->where('sucursal_id', (int) $branchId)->delete();

                    continue;
                }

                SucursalEvaluadorMbi::query()->updateOrCreate(
                    ['sucursal_id' => (int) $branchId],
                    ['profesional_id' => (int) $professionalId],
                );
            }

            return ['saved' => true, 'conflicts' => []];
        });

        if (! $result['saved']) {
            $this->conflicts = $result['conflicts'];
            Flux::toast(text: 'No se guardaron los cambios: hay citas futuras que quedarían fuera del horario.', variant: 'danger');

            return;
        }

        $this->groups = $configuration['groups'];
        $this->durationMinutes = $configuration['duration_minutes'];
        $this->closures = $configuration['closures'];
        $this->evaluadoresMbi = $validated['evaluadoresMbi'];
        $this->resetValidation();
        Flux::toast(text: 'La disponibilidad y los evaluadores MBI por sede se guardaron correctamente.', variant: 'success');
    }
}
