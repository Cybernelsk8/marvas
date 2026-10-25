<?php

namespace App\Livewire\Traits;

use App\Models\Cita;
use App\Services\ClinicAvailabilityService;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Collection;

/**
 * Comparte consultas y cálculos de disponibilidad de citas entre componentes Livewire.
 */
trait HasCitas
{
    public ?string $fecha = null;

    public ?string $hora = null;

    /**
     * Consulta al servicio los horarios recurrentes de la clínica.
     * @return array Consulta al servicio los horarios recurrentes de la clínica.
     */
    // Consulta al servicio los horarios recurrentes de la clínica.
    //   Ejemplo: $this->getConfiguredHours()
    //   Resultado: arreglo con los horarios semanales configurados.
    public function getConfiguredHours(): array
    {
        return app(ClinicAvailabilityService::class)->recurringHours();
    }

    /**
     * Busca citas vigentes que se cruzan con el rango e ignora, si aplica, una cita editada.
     * @param ?Carbon $desde Dato recibido por el componente.
     * @param ?Carbon $hasta Dato recibido por el componente.
     * @param ?int $ignoreAppointmentId Dato recibido por el componente.
     * @return Collection Busca citas vigentes que se cruzan con el rango e ignora, si aplica, una cita editada.
     */
    // Busca citas vigentes que se cruzan con el rango e ignora, si aplica, una cita editada.
    //   Ejemplo: $this->getCalendarAppointments(Carbon::today(), Carbon::today(), 12)
    //   Resultado: colección ordenada de ocupaciones, excluida la cita #12.
    public function getCalendarAppointments(
        ?Carbon $desde = null,
        ?Carbon $hasta = null,
        ?int $ignoreAppointmentId = null,
    ): Collection {
        $desde = ($desde ?? Carbon::today())->copy()->startOfDay();
        $hasta = ($hasta ?? $desde->copy()->addMonths(3))->copy()->startOfDay()->addDay();

        return Cita::query()
            ->whereNotIn('estado', ['cancelada', 'no_asistio'])
            ->when($ignoreAppointmentId, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->where('fecha_hora_inicio', '<', $hasta)
            ->where('fecha_hora_fin', '>', $desde)
            ->orderBy('fecha_hora_inicio')
            ->get(['id', 'fecha_hora_inicio', 'fecha_hora_fin']);
    }

    /**
     * Convierte cada cita en un evento agrupado por fecha para el calendario.
     * @param iterable $citas Dato recibido por el componente.
     * @return array Convierte cada cita en un evento agrupado por fecha para el calendario.
     */
    // Convierte cada cita en un evento agrupado por fecha para el calendario.
    //   Ejemplo: $this->getEvents($citas)
    //   Resultado: eventos “Ocupado” con hora y color rojo en cada día correspondiente.
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

    /**
     * Identifica fechas del rango que no tienen turnos posibles según duración y citas existentes.
     * @param array $horariosConfigurados Dato recibido por el componente.
     * @param ?iterable $citas Dato recibido por el componente.
     * @param ?Carbon $desde Dato recibido por el componente.
     * @param ?Carbon $hasta Dato recibido por el componente.
     * @param ?array $availabilityConfiguration Dato recibido por el componente.
     * @return array Identifica fechas del rango que no tienen turnos posibles según duración y citas existentes.
     */
    // Identifica fechas del rango que no tienen turnos posibles según duración y citas existentes.
    //   Ejemplo: $this->getFechasLlenas($horarios, $citas, availabilityConfiguration: $config)
    //   Resultado: arreglo de fechas ISO que el calendario puede deshabilitar.
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

    /**
     * Calcula horas futuras libres para la fecha y configuración recibidas.
     * @param string $fecha Dato recibido por el componente.
     * @param ?array $horariosConfigurados Dato recibido por el componente.
     * @param ?iterable $citas Dato recibido por el componente.
     * @param ?array $availabilityConfiguration Dato recibido por el componente.
     * @param ?int $ignoreAppointmentId Dato recibido por el componente.
     * @return array Calcula horas futuras libres para la fecha y configuración recibidas.
     */
    // Calcula horas futuras libres para la fecha y configuración recibidas.
    //   Ejemplo: $this->getAvailableHours("2026-08-03", availabilityConfiguration: $config)
    //   Resultado: lista HH:MM válida sin traslapes ni horarios fuera de configuración.
    public function getAvailableHours(
        string $fecha,
        ?array $horariosConfigurados = null,
        ?iterable $citas = null,
        ?array $availabilityConfiguration = null,
        ?int $ignoreAppointmentId = null,
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

        $citas ??= $this->getCalendarAppointments($fechaValida, $fechaValida, $ignoreAppointmentId);
        if ($ignoreAppointmentId !== null) {
            $citas = collect($citas)
                ->reject(fn ($cita): bool => isset($cita->id) && (int) $cita->id === $ignoreAppointmentId);
        }
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

    /**
     * Aplica reglas de futuro, configuración y traslape a los horarios candidatos.
     * @param string $fecha Dato recibido por el componente.
     * @param array $horarios Dato recibido por el componente.
     * @param iterable $citas Dato recibido por el componente.
     * @param ?array $availabilityConfiguration Dato recibido por el componente.
     * @param int $durationMinutes Dato recibido por el componente.
     * @return array Aplica reglas de futuro, configuración y traslape a los horarios candidatos.
     */
    // Aplica reglas de futuro, configuración y traslape a los horarios candidatos.
    //   Ejemplo: se usa internamente para evaluar un día al calcular horas o fechas llenas.
    //   Resultado: solo quedan turnos permitidos y libres durante su duración.
    protected function availableHoursForDate(
        string $fecha,
        array $horarios,
        iterable $citas,
        ?array $availabilityConfiguration = null,
        int $durationMinutes = 60,
    ): array {
        $horarios = collect($horarios)
            ->map(fn ($horario) => Carbon::parse($horario)->format('H:i'))
            ->unique()
            ->values();

        return $horarios
            ->reject(function (string $horario) use ($fecha, $citas, $availabilityConfiguration, $durationMinutes) {
                $inicio = Carbon::parse("{$fecha} {$horario}");
                $fin = $inicio->copy()->addMinutes($durationMinutes);

                if (! $inicio->isFuture()) {
                    return true;
                }

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

    /**
     * Distribuye cada cita en todos los días que ocupa, sin incluir el instante final.
     * @param iterable $citas Dato recibido por el componente.
     * @return array Distribuye cada cita en todos los días que ocupa, sin incluir el instante final.
     */
    // Distribuye cada cita en todos los días que ocupa, sin incluir el instante final.
    //   Ejemplo: una cita de 23:30 a 00:30 cruza dos fechas.
    //   Resultado: la cita aparece agrupada en ambos días que bloquea.
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
