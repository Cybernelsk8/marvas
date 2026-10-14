<?php

namespace App\Livewire\Traits;

use App\Models\Cita;
use App\Services\ClinicAvailabilityService;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Collection;

trait HasCitas
{
    public ?string $fecha = null;

    public ?string $hora = null;

    public function getConfiguredHours(): array
    {
        return app(ClinicAvailabilityService::class)->recurringHours();
    }

    public function getCalendarAppointments(?Carbon $desde = null, ?Carbon $hasta = null): Collection
    {
        $desde = ($desde ?? Carbon::today())->copy()->startOfDay();
        $hasta = ($hasta ?? $desde->copy()->addMonths(3))->copy()->startOfDay()->addDay();

        return Cita::query()
            ->whereNotIn('estado', ['cancelada', 'no_asistio'])
            ->where('fecha_hora_inicio', '<', $hasta)
            ->where('fecha_hora_fin', '>', $desde)
            ->orderBy('fecha_hora_inicio')
            ->get(['id', 'fecha_hora_inicio', 'fecha_hora_fin']);
    }

    public function getEvents(iterable $citas): array
    {
        $eventos = [];

        foreach ($citas as $cita) {
            $fecha = $cita->fecha_hora_inicio->toDateString();
            $eventos[$fecha][] = [
                'title' => 'Ocupado',
                'time' => $cita->fecha_hora_inicio->format('H:i'),
                'color' => '#dc2626',
            ];
        }

        return $eventos;
    }

    public function getFechasLlenas(
        array $horariosConfigurados,
        ?iterable $citas = null,
        ?Carbon $desde = null,
        ?Carbon $hasta = null,
        ?array $availabilityConfiguration = null,
    ): array {
        $desde = ($desde ?? Carbon::today())->copy()->startOfDay();
        $hasta = ($hasta ?? $desde->copy()->addMonths(3))->copy()->startOfDay();
        $citas = $citas ?? $this->getCalendarAppointments($desde, $hasta);
        $citasPorFecha = $this->agruparCitasPorFecha($citas);
        $durationMinutes = $availabilityConfiguration['duration_minutes'] ?? 60;
        $fechasLlenas = [];

        for ($fecha = $desde->copy(); $fecha->lte($hasta); $fecha->addDay()) {
            $fechaIso = $fecha->toDateString();
            $horariosDelDia = $availabilityConfiguration === null
                ? $horariosConfigurados
                : app(ClinicAvailabilityService::class)->hoursForDay($fecha->dayOfWeekIso, $availabilityConfiguration);

            if ($this->availableHoursForDate(
                $fechaIso,
                $horariosDelDia,
                $citasPorFecha[$fechaIso] ?? [],
                $availabilityConfiguration,
                $durationMinutes,
            ) === []) {
                $fechasLlenas[] = $fechaIso;
            }
        }

        return $fechasLlenas;
    }

    public function getAvailableHours(
        string $fecha,
        ?array $horariosConfigurados = null,
        ?iterable $citas = null,
        ?array $availabilityConfiguration = null,
    ): array {
        try {
            $fechaValida = Carbon::createFromFormat('!Y-m-d', $fecha);
        } catch (InvalidFormatException) {
            return [];
        }

        if (! $fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
            return [];
        }

        if ($availabilityConfiguration !== null) {
            $horariosConfigurados = app(ClinicAvailabilityService::class)
                ->hoursForDay($fechaValida->dayOfWeekIso, $availabilityConfiguration);
        } elseif ($horariosConfigurados === null) {
            $availability = app(ClinicAvailabilityService::class);
            $availabilityConfiguration ??= $availability->configuration();
            $horariosConfigurados = $availability->hoursForDay($fechaValida->dayOfWeekIso, $availabilityConfiguration);
        }

        $citas ??= $this->getCalendarAppointments($fechaValida, $fechaValida);
        $citasPorFecha = $this->agruparCitasPorFecha($citas);
        $durationMinutes = $availabilityConfiguration['duration_minutes'] ?? 60;

        return $this->availableHoursForDate(
            $fecha,
            $horariosConfigurados,
            $citasPorFecha[$fecha] ?? [],
            $availabilityConfiguration,
            $durationMinutes,
        );
    }

    protected function availableHoursForDate(
        string $fecha,
        array $horarios,
        iterable $citas,
        ?array $availabilityConfiguration = null,
        int $durationMinutes = 60,
    ): array
    {
        $horarios = collect($horarios)
            ->map(fn ($horario) => Carbon::parse($horario)->format('H:i'))
            ->unique()
            ->values();

        return $horarios
            ->reject(function (string $horario) use ($fecha, $citas, $availabilityConfiguration, $durationMinutes) {
                $inicio = Carbon::parse("{$fecha} {$horario}");
                $fin = $inicio->copy()->addMinutes($durationMinutes);

                if ($availabilityConfiguration !== null
                    && ! app(ClinicAvailabilityService::class)->isSlotAllowed($inicio, $durationMinutes, $availabilityConfiguration)) {
                    return true;
                }

                foreach ($citas as $cita) {
                    if ($cita->fecha_hora_inicio->lt($fin) && $cita->fecha_hora_fin->gt($inicio)) {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();
    }

    private function agruparCitasPorFecha(iterable $citas): array
    {
        $citasPorFecha = [];

        foreach ($citas as $cita) {
            $inicio = $cita->fecha_hora_inicio;
            $fin = $cita->fecha_hora_fin;
            $primerDia = $inicio->copy()->startOfDay();
            $ultimoDia = $fin->copy()->subMicrosecond()->startOfDay();

            if ($ultimoDia->lt($primerDia)) {
                continue;
            }

            $diasDeCita = (int) $primerDia->diffInDays($ultimoDia);

            for ($offset = 0; $offset <= $diasDeCita; $offset++) {
                $fecha = $primerDia->copy()->addDays($offset);
                $citasPorFecha[$fecha->toDateString()][] = $cita;
            }
        }

        return $citasPorFecha;
    }
}
