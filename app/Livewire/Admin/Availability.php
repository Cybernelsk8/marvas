<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Setting;
use App\Services\ClinicAvailabilityService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Disponibilidad')]
class Availability extends Component
{
    public array $groups = [];

    public int $durationMinutes = 60;

    public array $closures = [];

    public array $conflicts = [];

    public function mount(): void
    {
        $this->authorize('page.view.schedule');

        $configuration = app(ClinicAvailabilityService::class)->configuration();
        $this->groups = array_values($configuration['groups']);
        $this->durationMinutes = $configuration['duration_minutes'];
        $this->closures = array_values($configuration['closures']);
    }

    public function addGroup(): void
    {
        $this->authorize('schedule.edit');

        if (count($this->groups) < 7) {
            $this->groups[] = ['days' => [], 'hours' => []];
        }
    }

    public function removeGroup(int $index): void
    {
        $this->authorize('schedule.edit');

        if (array_key_exists($index, $this->groups)) {
            unset($this->groups[$index]);
            $this->groups = array_values($this->groups);
        }
    }

    public function addHour(int $groupIndex): void
    {
        $this->authorize('schedule.edit');

        if (isset($this->groups[$groupIndex])) {
            $this->groups[$groupIndex]['hours'][] = '';
        }
    }

    public function removeHour(int $groupIndex, int $hourIndex): void
    {
        $this->authorize('schedule.edit');

        if (isset($this->groups[$groupIndex]['hours'][$hourIndex])) {
            unset($this->groups[$groupIndex]['hours'][$hourIndex]);
            $this->groups[$groupIndex]['hours'] = array_values($this->groups[$groupIndex]['hours']);
        }
    }

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
        ]);
    }

    public function addClosure(): void
    {
        $this->authorize('schedule.edit');

        $this->closures[] = [
            'start_date' => '',
            'end_date' => '',
            'reason' => '',
        ];
    }

    public function removeClosure(int $index): void
    {
        $this->authorize('schedule.edit');

        if (array_key_exists($index, $this->closures)) {
            unset($this->closures[$index]);
            $this->closures = array_values($this->closures);
        }
    }

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
        ]);

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
        $result = DB::transaction(function () use ($configuration): array {
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
        $this->resetValidation();
        Flux::toast(text: 'La disponibilidad global se guardó correctamente.', variant: 'success');
    }
}
