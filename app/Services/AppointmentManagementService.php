<?php

namespace App\Services;

use App\Models\Admin\Setting;
use App\Models\Cita;
use App\Models\Sucursal;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AppointmentManagementService
{
    public function confirm(int $citaId, int $sucursalId, int $userId): string
    {
        return DB::transaction(function () use ($citaId, $sucursalId, $userId): string {
            $sucursalActiva = Sucursal::query()
                ->whereKey($sucursalId)
                ->where('activa', true)
                ->lockForUpdate()
                ->first(['id']);

            if (! $sucursalActiva) {
                return 'sede_no_disponible';
            }

            $cita = Cita::query()->lockForUpdate()->findOrFail($citaId);

            if (! in_array($cita->estado, ['solicitada', 'reprogramada', 'agendada'], true)) {
                return 'no_pendiente';
            }

            if (! $cita->fecha_hora_inicio->isFuture()
                || Cita::existeTraslapeGlobal($cita->fecha_hora_inicio, $cita->fecha_hora_fin, $cita->id)) {
                return 'no_disponible';
            }

            $cita->estado = 'confirmada';
            $cita->sucursal_id = $sucursalId;
            $cita->agendada_por = $userId;
            $cita->save();

            return 'confirmada';
        });
    }

    public function reschedule(int $citaId, CarbonInterface $inicio): string
    {
        return DB::transaction(function () use ($citaId, $inicio): string {
            $horarios = Setting::query()
                ->where('key', 'horarios_citas')
                ->lockForUpdate()
                ->first()?->value;

            if (! is_array($horarios)) {
                return 'sin_configuracion';
            }

            $cita = Cita::query()->lockForUpdate()->findOrFail($citaId);

            if ($cita->estado !== 'solicitada') {
                return 'no_pendiente';
            }

            $fin = $inicio->copy()->addMinutes($cita->duracion_minutos);

            if (! $inicio->isFuture()
                || ! in_array($inicio->format('H:i'), $horarios, true)
                || Cita::existeTraslapeGlobal($inicio, $fin, $cita->id)) {
                return 'no_disponible';
            }

            $cita->fecha_hora_inicio = $inicio;
            $cita->fecha_hora_fin = $fin;
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
