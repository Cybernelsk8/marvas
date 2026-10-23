<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Sucursal;
use App\Models\SucursalEvaluadorMbi;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AppointmentManagementService
{
    public function confirm(int $citaId, int $sucursalId, int $userId, int $profesionalId, int $especialidadId): string
    {
        return DB::transaction(function () use ($citaId, $sucursalId, $userId, $profesionalId, $especialidadId): string {
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

            $assignmentResult = $this->validateAssignment(
                $sucursalId,
                $profesionalId,
                $especialidadId,
                $cita->tipo_cita === 'primera_vez',
            );

            if ($assignmentResult !== 'asignacion_valida') {
                return $assignmentResult;
            }

            if (! $cita->fecha_hora_inicio->isFuture()
                || ! $availability->isSlotAllowed($cita->fecha_hora_inicio, $cita->duracion_minutos, $configuration)
                || Cita::existeTraslape($profesionalId, $cita->fecha_hora_inicio, $cita->fecha_hora_fin, $cita->id)) {
                return 'no_disponible';
            }

            $cita->estado = 'confirmada';
            $cita->sucursal_id = $sucursalId;
            $cita->profesional_id = $profesionalId;
            $cita->especialidad_id = $especialidadId;
            $cita->agendada_por = $userId;
            $cita->save();

            return 'confirmada';
        });
    }

    public function assignConfirmedAppointment(
        int $citaId,
        int $sucursalId,
        int $userId,
        int $profesionalId,
        int $especialidadId,
    ): string {
        return DB::transaction(function () use ($citaId, $sucursalId, $userId, $profesionalId, $especialidadId): string {
            $sucursalActiva = Sucursal::query()
                ->whereKey($sucursalId)
                ->where('activa', true)
                ->lockForUpdate()
                ->first(['id']);

            if (! $sucursalActiva) {
                return 'sede_no_disponible';
            }

            $cita = Cita::query()->lockForUpdate()->findOrFail($citaId);

            if ($cita->estado !== 'confirmada'
                || ($cita->sucursal_id && $cita->profesional_id && $cita->especialidad_id)) {
                return 'no_asignable';
            }

            $assignmentResult = $this->validateAssignment(
                $sucursalId,
                $profesionalId,
                $especialidadId,
                $cita->tipo_cita === 'primera_vez',
            );

            if ($assignmentResult !== 'asignacion_valida') {
                return $assignmentResult;
            }

            if (Cita::existeTraslape($profesionalId, $cita->fecha_hora_inicio, $cita->fecha_hora_fin, $cita->id)) {
                return 'no_disponible';
            }

            $previous = [
                'sucursal_id' => $cita->sucursal_id,
                'profesional_id' => $cita->profesional_id,
                'especialidad_id' => $cita->especialidad_id,
            ];
            $cita->sucursal_id = $sucursalId;
            $cita->profesional_id = $profesionalId;
            $cita->especialidad_id = $especialidadId;
            $cita->save();

            Auditoria::create([
                'user_id' => $userId,
                'tabla_afectada' => 'citas',
                'registro_id' => $cita->id,
                'accion' => 'editar',
                'datos_anteriores' => $previous,
                'datos_nuevos' => [
                    'sucursal_id' => $sucursalId,
                    'profesional_id' => $profesionalId,
                    'especialidad_id' => $especialidadId,
                ],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return 'asignada';
        });
    }

    private function validateAssignment(
        int $sucursalId,
        int $profesionalId,
        int $especialidadId,
        bool $requiresMbiEvaluator,
    ): string {
        if (! Especialidad::query()->whereKey($especialidadId)->where('activa', true)->exists()) {
            return 'especialidad_no_disponible';
        }

        if ($requiresMbiEvaluator) {
            $mbiSpecialty = Especialidad::query()
                ->where('slug', 'evaluacion-mbi')
                ->where('activa', true)
                ->first();
            $isAssignedEvaluator = SucursalEvaluadorMbi::query()
                ->where('sucursal_id', $sucursalId)
                ->where('profesional_id', $profesionalId)
                ->exists();

            if (! $isAssignedEvaluator) {
                return 'evaluador_no_disponible';
            }

            if (! $mbiSpecialty || $especialidadId !== $mbiSpecialty->id) {
                return 'especialidad_no_disponible';
            }
        }

        $professionalAvailableAtBranch = Profesional::query()
            ->whereKey($profesionalId)
            ->where('activo', true)
            ->whereHas('sucursales', fn ($query) => $query->whereKey($sucursalId))
            ->lockForUpdate()
            ->exists();

        if (! $professionalAvailableAtBranch) {
            return 'profesional_no_disponible';
        }

        $specialtyBelongsToProfessional = Profesional::query()
            ->whereKey($profesionalId)
            ->whereHas('especialidades', fn ($query) => $query->whereKey($especialidadId)->where('activa', true))
            ->exists();

        return $specialtyBelongsToProfessional ? 'asignacion_valida' : 'especialidad_no_disponible';
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
