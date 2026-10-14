<?php

namespace App\Services;

use App\Models\Admin\Setting;
use App\Models\Cita;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use UnexpectedValueException;

class ClinicAvailabilityService
{
    public const SETTING_KEY = 'disponibilidad_global_citas';

    public const MIN_DURATION_MINUTES = 15;

    public const MAX_DURATION_MINUTES = 1380;

    public const DEFAULT_CONFIGURATION = [
        'groups' => [[
            'days' => [1, 2, 3, 4, 5],
            'hours' => ['08:00', '09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00'],
        ]],
        'duration_minutes' => 60,
        'closures' => [],
    ];

    public function configuration(bool $lockForUpdate = false): array
    {
        $stored = $lockForUpdate
            ? $this->lockSetting()->value
            : Setting::query()->where('key', self::SETTING_KEY)->first()?->value;

        if ($stored === null) {
            return self::DEFAULT_CONFIGURATION;
        }

        if (! is_array($stored)) {
            throw new UnexpectedValueException('La configuración global de disponibilidad no tiene un formato válido.');
        }

        if (! isset($stored['groups'])) {
            $stored = $this->convertLegacyConfiguration($stored);
        }

        $configuration = array_replace(self::DEFAULT_CONFIGURATION, $stored);

        $configuration['duration_minutes'] = filter_var(
            $configuration['duration_minutes'],
            FILTER_VALIDATE_INT,
        );

        if (! is_array($configuration['groups'])
            || ! is_array($configuration['closures'])
            || $configuration['duration_minutes'] === false
            || $configuration['duration_minutes'] < self::MIN_DURATION_MINUTES
            || $configuration['duration_minutes'] > self::MAX_DURATION_MINUTES
            || $configuration['duration_minutes'] % 15 !== 0) {
            throw new UnexpectedValueException('La configuración global de disponibilidad está incompleta o es inválida.');
        }

        $assignedDays = [];
        foreach ($configuration['groups'] as $group) {
            if (! is_array($group)
                || ! isset($group['days'], $group['hours'])
                || ! is_array($group['days'])
                || ! is_array($group['hours'])
                || $group['days'] === []) {
                throw new UnexpectedValueException('Un grupo de disponibilidad tiene un formato inválido.');
            }

            foreach ($group['days'] as $day) {
                if (! is_int($day) || $day < 1 || $day > 7 || in_array($day, $assignedDays, true)) {
                    throw new UnexpectedValueException('Los días configurados están repetidos o son inválidos.');
                }
                $assignedDays[] = $day;
            }

            if (! $this->hoursAreValid($group['hours'], $configuration['duration_minutes'])) {
                throw new UnexpectedValueException('Las horas de disponibilidad están repetidas, se traslapan o son inválidas.');
            }
        }

        return $configuration;
    }

    public function lockSetting(): Setting
    {
        Setting::query()->firstOrCreate(
            ['key' => self::SETTING_KEY],
            [
                'type' => 'array',
                'value' => self::DEFAULT_CONFIGURATION,
                'description' => 'Horario global, pausa y cierres para las reservas de la clínica.',
            ],
        );

        return Setting::query()
            ->where('key', self::SETTING_KEY)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function hoursForDay(int $dayOfWeek, ?array $configuration = null): array
    {
        $configuration ??= $this->configuration();
        foreach ($configuration['groups'] as $group) {
            if (in_array($dayOfWeek, $group['days'], true)) {
                return $group['hours'];
            }
        }

        return [];
    }

    public function recurringHours(?array $configuration = null): array
    {
        $configuration ??= $this->configuration();

        return collect($configuration['groups'])
            ->flatMap(fn (array $group) => $group['hours'])
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function isSlotAllowed(
        CarbonInterface $start,
        int $durationMinutes,
        ?array $configuration = null,
    ): bool {
        $configuration ??= $this->configuration();

        if ($durationMinutes !== (int) $configuration['duration_minutes']
            || $this->isClosed($start, $configuration['closures'])) {
            return false;
        }

        return in_array(
            $start->format('H:i'),
            $this->hoursForDay($start->dayOfWeekIso, $configuration),
            true,
        ) && $start->copy()->addMinutes($durationMinutes)->toDateString() === $start->toDateString();
    }

    public function appointmentsConflictingWith(array $configuration): Collection
    {
        $appointments = Cita::query()
            ->with('expediente.paciente')
            ->where('fecha_hora_inicio', '>=', now())
            ->whereNotIn('estado', ['cancelada', 'no_asistio'])
            ->orderBy('fecha_hora_inicio')
            ->lockForUpdate()
            ->get();

        return $appointments
            ->filter(fn (Cita $appointment) => ! $this->isSlotAllowed(
                $appointment->fecha_hora_inicio,
                $appointment->duracion_minutos,
                $configuration,
            ))
            ->values();
    }

    private function isClosed(CarbonInterface $start, array $closures): bool
    {
        $date = $start->toDateString();

        foreach ($closures as $closure) {
            if (! is_array($closure)
                || ! isset($closure['start_date'], $closure['end_date'])
                || ! is_string($closure['start_date'])
                || ! is_string($closure['end_date'])) {
                throw new UnexpectedValueException('Un cierre de disponibilidad tiene un formato inválido.');
            }

            if ($date >= $closure['start_date'] && $date <= $closure['end_date']) {
                return true;
            }
        }

        return false;
    }

    private function hoursAreValid(array $hours, int $durationMinutes): bool
    {
        foreach ($hours as $hour) {
            if (! is_string($hour)
                || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hour)
                || Carbon::createFromFormat('!H:i', $hour)->addMinutes($durationMinutes)->format('Y-m-d') !== '1970-01-01') {
                return false;
            }
        }

        if (count($hours) !== count(array_unique($hours))) {
            return false;
        }

        $sorted = $hours;
        sort($sorted);

        for ($index = 1; $index < count($sorted); $index++) {
            $previousEnd = Carbon::createFromFormat('!H:i', $sorted[$index - 1])->addMinutes($durationMinutes);
            $currentStart = Carbon::createFromFormat('!H:i', $sorted[$index]);

            if ($previousEnd->gt($currentStart)) {
                return false;
            }
        }

        return true;
    }

    private function convertLegacyConfiguration(array $stored): array
    {
        if (! isset($stored['days'], $stored['opening_time'], $stored['closing_time'], $stored['break_start'], $stored['break_end'])
            || ! is_array($stored['days'])) {
            throw new UnexpectedValueException('La configuración de disponibilidad guardada no es válida.');
        }

        $start = Carbon::createFromFormat('!H:i', $stored['opening_time']);
        $closing = Carbon::createFromFormat('!H:i', $stored['closing_time']);
        $breakStart = Carbon::createFromFormat('!H:i', $stored['break_start']);
        $breakEnd = Carbon::createFromFormat('!H:i', $stored['break_end']);
        $hours = [];

        while ($start->copy()->addHour()->lte($closing)) {
            $end = $start->copy()->addHour();
            if (! ($start->lt($breakEnd) && $end->gt($breakStart))) {
                $hours[] = $start->format('H:i');
            }
            $start->addHour();
        }

        return [
            'groups' => $stored['days'] === [] ? [] : [[
                'days' => array_values(array_map('intval', $stored['days'])),
                'hours' => $hours,
            ]],
            'duration_minutes' => (int) ($stored['duration_minutes'] ?? 60),
            'closures' => $stored['closures'] ?? [],
        ];
    }
}
