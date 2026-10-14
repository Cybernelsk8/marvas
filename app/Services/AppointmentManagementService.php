<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Profesional;
use App\Models\Sucursal;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AppointmentManagementService
{
    public function confirm(int $citaId, int $sucursalId, int $userId, ?int $profesionalId = null): string
    {
        return DB::transaction(function () use ($citaId, $sucursalId, $userId, $profesionalId): string {
            $sucursalActiva = Sucursal::query()
                ->whereKey($sucursalId)
                ->where('activa', true)
                ->lockForUpdate()
                ->first(['id']);

            if (! $sucursalActiva) {
                return 'sede_no_disponible';
            }

            $availability = app(ClinicAvailabilityService::class);
            $configuration = $availability->configuration(lockForUpdate: true);
            $cita = Cita::query()->lockForUpdate()->findOrFail($citaId);

            if (! in_array($cita->estado, ['solicitada', 'reprogramada', 'agendada'], true)) {
                return 'no_pendiente';
            }

            if ($profesionalId !== null) {
                $profesionalAsignado = Profesional::query()
                    ->whereKey($profesionalId)
                    ->where('activo', true)
                    ->whereHas('sucursales', fn ($query) => $query->whereKey($sucursalId))
                    ->lockForUpdate()
                    ->exists();

                if (! $profesionalAsignado) {
                    return 'profesional_no_disponible';
                }
            }

            if (! $cita->fecha_hora_inicio->isFuture()
                || ! $availability->isSlotAllowed($cita->fecha_hora_inicio, $cita->duracion_minutos, $configuration)
                || ($profesionalId !== null
                    ? Cita::existeTraslape($profesionalId, $cita->fecha_hora_inicio, $cita->fecha_hora_fin, $cita->id)
                    : Cita::existeTraslapeGlobal($cita->fecha_hora_inicio, $cita->fecha_hora_fin, $cita->id))) {
                return 'no_disponible';
            }

            $cita->estado = 'confirmada';
            $cita->sucursal_id = $sucursalId;
            $cita->profesional_id = $profesionalId;
            $cita->agendada_por = $userId;
            $cita->save();

            return 'confirmada';
        });
    }

    public function reschedule(int $citaId, CarbonInterface $inicio): string
    {
        return DB::transaction(function () use ($citaId, $inicio): string {
            $availability = app(ClinicAvailabilityService::class);
            $configuration = $availability->configuration(lockForUpdate: true);
            $cita = Cita::query()->lockForUpdate()->findOrFail($citaId);

            if ($cita->estado !== 'solicitada') {
                return 'no_pendiente';
            }

            $duracion = (int) $configuration['duration_minutes'];
            $fin = $inicio->copy()->addMinutes($duracion);
            $profesionalId = $cita->profesional_id;

            if (! $inicio->isFuture()
                || ! $availability->isSlotAllowed($inicio, $duracion, $configuration)
                || ($profesionalId !== null
                    ? Cita::existeTraslape($profesionalId, $inicio, $fin, $cita->id)
                    : Cita::existeTraslapeGlobal($inicio, $fin, $cita->id))) {
                return 'no_disponible';
            }

            $cita->fecha_hora_inicio = $inicio;
            $cita->fecha_hora_fin = $fin;
            $cita->duracion_minutos = $duracion;
            $cita->estado = 'solicitada';
            $cita->save();

            return 'reprogramada';
        });
    }

    public function cancel(int $citaId, string $motivo): string
    {
        return DB::transaction(function () use ($citaId, $motivo): string {
            $cita = Cita::query()->lockForUpdate()->findOrFail($citaId);

            if ($cita->estado !== 'solicitada') {
                return 'no_pendiente';
            }

            $cita->estado = 'cancelada';
            $cita->motivo_cancelacion = trim($motivo);
            $cita->save();

            return 'cancelada';
        });
    }
}
