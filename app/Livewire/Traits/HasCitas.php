<?php

namespace App\Livewire\Traits;

use App\Models\Admin\Setting;
use App\Models\Cita;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Collection;

trait HasCitas
{
    public ?string $fecha = null;

    public ?string $hora = null;

    public function getConfiguredHours(): array
    {
        $horarios = Setting::query()
            ->where('key', 'horarios_citas')
            ->first()?->value;

        if (! is_array($horarios)) {
            return [];
        }

        return collect($horarios)
            ->map(function ($horario) {
                if (! is_string($horario) || trim($horario) === '') {
                    return null;
                }

                try {
                    return Carbon::parse($horario)->format('H:i');
                } catch (InvalidFormatException) {
                    return null;
                }
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
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
    ): array {
        $desde = ($desde ?? Carbon::today())->copy()->startOfDay();
        $hasta = ($hasta ?? $desde->copy()->addMonths(3))->copy()->startOfDay();
        $citas = $citas ?? $this->getCalendarAppointments($desde, $hasta);
        $citasPorFecha = $this->agruparCitasPorFecha($citas);
        $fechasLlenas = [];

        for ($fecha = $desde->copy(); $fecha->lte($hasta); $fecha->addDay()) {
            $fechaIso = $fecha->toDateString();

            if ($this->availableHoursForDate($fechaIso, $horariosConfigurados, $citasPorFecha[$fechaIso] ?? []) === []) {
                $fechasLlenas[] = $fechaIso;
            }
        }

        return $fechasLlenas;
    }

    public function getAvailableHours(
        string $fecha,
        ?array $horariosConfigurados = null,
        ?iterable $citas = null,
    ): array {
        try {
            $fechaValida = Carbon::createFromFormat('!Y-m-d', $fecha);
        } catch (InvalidFormatException) {
            return [];
        }

        if (! $fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
            return [];
        }

        $horariosConfigurados ??= $this->getConfiguredHours();
        $citas ??= $this->getCalendarAppointments($fechaValida, $fechaValida);
        $citasPorFecha = $this->agruparCitasPorFecha($citas);

        return $this->availableHoursForDate($fecha, $horariosConfigurados, $citasPorFecha[$fecha] ?? []);
    }

    protected function availableHoursForDate(string $fecha, array $horarios, iterable $citas): array
    {
        $horarios = collect($horarios)
            ->map(fn ($horario) => Carbon::parse($horario)->format('H:i'))
            ->unique()
            ->values();

        return $horarios
            ->reject(function (string $horario) use ($fecha, $citas) {
                $inicio = Carbon::parse("{$fecha} {$horario}");
                $fin = $inicio->copy()->addMinutes(60);

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
