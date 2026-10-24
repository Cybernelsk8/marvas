<?php

namespace App\Services;

use App\Models\AnamnesisAcupuntura;
use App\Models\AnamnesisGeneral;
use App\Models\AnamnesisHomeopatica;
use App\Models\AnamnesisQuiropraxia;
use App\Models\Auditoria;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\EvaluacionIntegralMbi;
use App\Models\ExpedienteMaestro;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsultationService
{
    public function saveMbiEvaluation(int $consultationId, array $data): EvaluacionIntegralMbi
    {
        return DB::transaction(function () use ($consultationId, $data): EvaluacionIntegralMbi {
            $consultation = Consulta::query()
                ->whereKey($consultationId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($consultation->estado, ['abierta', 'en_proceso'], true)) {
                throw ValidationException::withMessages([
                    'mbi' => 'Solo se puede guardar la evaluación MBI de una consulta abierta o en proceso.',
                ]);
            }

            $evaluation = EvaluacionIntegralMbi::query()
                ->where('consulta_id', $consultation->id)
                ->lockForUpdate()
                ->first();
            $isNew = $evaluation === null;
            $evaluation ??= new EvaluacionIntegralMbi(['consulta_id' => $consultation->id]);
            $evaluation->fill($data);
            $evaluation->consulta_id = $consultation->id;
            $evaluation->save();

            $changedFields = collect(array_keys($data))
                ->filter(fn (string $field): bool => $isNew || $evaluation->wasChanged($field))
                ->values()
                ->all();

            Auditoria::create([
                'user_id' => Auth::id(),
                'tabla_afectada' => 'evaluacion_integral_mbi',
                'registro_id' => $evaluation->id,
                'accion' => $isNew ? 'crear' : 'editar',
                'datos_anteriores' => $isNew ? null : [
                    'consulta_id' => $consultation->id,
                    'campos_modificados' => $changedFields,
                ],
                'datos_nuevos' => [
                    'consulta_id' => $consultation->id,
                    'campos_modificados' => $changedFields,
                ],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $evaluation->fresh();
        });
    }

    public function saveGeneralAnamnesis(int $consultationId, array $data): AnamnesisGeneral
    {
        return DB::transaction(function () use ($consultationId, $data): AnamnesisGeneral {
            $consultation = Consulta::query()
                ->whereKey($consultationId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($consultation->estado, ['abierta', 'en_proceso'], true)) {
                throw ValidationException::withMessages([
                    'anamnesis' => 'Solo se puede guardar la anamnesis de una consulta abierta o en proceso.',
                ]);
            }

            return $this->persistGeneralAnamnesis($consultation, $data);
        });
    }

    public function saveIntegralAnamnesis(
        int $consultationId,
        array $generalData,
        array $assessments,
        array $additionalData,
    ): AnamnesisGeneral {
        return DB::transaction(function () use ($consultationId, $generalData, $assessments, $additionalData): AnamnesisGeneral {
            $consultation = Consulta::query()
                ->whereKey($consultationId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($consultation->estado, ['abierta', 'en_proceso'], true)) {
                throw ValidationException::withMessages([
                    'anamnesis' => 'Solo se puede guardar la anamnesis de una consulta abierta o en proceso.',
                ]);
            }

            $anamnesis = $this->persistGeneralAnamnesis($consultation, $generalData);
            $this->persistAssessmentForms($anamnesis, $assessments);

            foreach ($additionalData as $formKey => $data) {
                $this->persistAdditionalAssessmentData($anamnesis, $formKey, $data);
            }

            return $anamnesis->fresh();
        });
    }

    public function saveAssessmentForm(
        int $consultationId,
        string $formKey,
        array $data,
        array $additionalData = [],
    ): AnamnesisGeneral {
        return DB::transaction(function () use ($consultationId, $formKey, $data, $additionalData): AnamnesisGeneral {
            $consultation = Consulta::query()
                ->whereKey($consultationId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($consultation->estado, ['abierta', 'en_proceso'], true)) {
                throw ValidationException::withMessages([
                    'anamnesis' => 'Solo se puede guardar la anamnesis de una consulta abierta o en proceso.',
                ]);
            }

            $anamnesis = $consultation->anamnesisGeneral()->lockForUpdate()->first();

            if (! $anamnesis) {
                $anamnesis = $this->persistGeneralAnamnesis($consultation, ['motivo_consulta' => null]);
            }

            $this->persistAssessmentForms($anamnesis, [$formKey => $data]);
            $this->persistAdditionalAssessmentData($anamnesis, $formKey, $additionalData);

            return $anamnesis->fresh();
        });
    }

    public function closeConsultation(int $consultationId, array $closureData): Consulta
    {
        return DB::transaction(function () use ($consultationId, $closureData): Consulta {
            $consultation = Consulta::query()
                ->whereKey($consultationId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($consultation->estado, ['abierta', 'en_proceso'], true)) {
                throw ValidationException::withMessages([
                    'consultation' => 'Solo se puede cerrar una consulta abierta o en proceso.',
                ]);
            }

            if (blank($closureData['motivo_consulta'] ?? null)) {
                throw ValidationException::withMessages([
                    'closure.motivo_consulta' => 'El motivo de consulta es obligatorio para cerrar.',
                ]);
            }

            if (blank($closureData['observaciones_profesional'] ?? null)) {
                throw ValidationException::withMessages([
                    'closure.observaciones_profesional' => 'Las observaciones finales son obligatorias para cerrar.',
                ]);
            }

            $anamnesis = $consultation->anamnesisGeneral()->lockForUpdate()->first();
            $anamnesisData = [];

            if ($anamnesis) {
                foreach (array_diff((new AnamnesisGeneral)->getFillable(), [
                    'consulta_id',
                    'especialidad_id',
                    'motivo_consulta',
                    'observaciones_profesional',
                ]) as $field) {
                    $anamnesisData[$field] = $anamnesis->{$field};
                }
            }

            $anamnesisData['motivo_consulta'] = $closureData['motivo_consulta'];
            $anamnesisData['observaciones_profesional'] = $closureData['observaciones_profesional'];
            $this->persistGeneralAnamnesis($consultation, $anamnesisData);

            $previousState = $consultation->estado;
            $consultation->estado = 'cerrada';
            $consultation->save();

            $this->auditTransition($consultation, 'cerrar', $previousState, 'cerrada');

            return $consultation->fresh();
        });
    }

    public function reopenConsultation(int $consultationId): Consulta
    {
        return DB::transaction(function () use ($consultationId): Consulta {
            $consultation = Consulta::query()
                ->whereKey($consultationId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($consultation->estado !== 'cerrada') {
                throw ValidationException::withMessages([
                    'consultation' => 'Solo se puede reabrir una consulta cerrada.',
                ]);
            }

            $consultation->estado = 'en_proceso';
            $consultation->save();

            $this->auditTransition($consultation, 'reabrir', 'cerrada', 'en_proceso');

            return $consultation->fresh();
        });
    }

    private function persistGeneralAnamnesis(Consulta $consultation, array $data): AnamnesisGeneral
    {
        $anamnesis = AnamnesisGeneral::query()
            ->where('consulta_id', $consultation->id)
            ->lockForUpdate()
            ->first();
        $isNew = $anamnesis === null;
        $anamnesis ??= new AnamnesisGeneral([
            'consulta_id' => $consultation->id,
            'especialidad_id' => $consultation->especialidad_id,
        ]);

        $anamnesis->fill($data);
        $anamnesis->consulta_id = $consultation->id;
        $anamnesis->especialidad_id = $consultation->especialidad_id;
        $anamnesis->save();

        $changedFields = collect(array_keys($data))
            ->filter(fn (string $field): bool => $isNew || $anamnesis->wasChanged($field))
            ->values()
            ->all();

        Auditoria::create([
            'user_id' => Auth::id(),
            'tabla_afectada' => 'anamnesis_generales',
            'registro_id' => $anamnesis->id,
            'accion' => $isNew ? 'crear' : 'editar',
            'datos_anteriores' => $isNew ? null : [
                'consulta_id' => $consultation->id,
                'campos_modificados' => $changedFields,
            ],
            'datos_nuevos' => [
                'consulta_id' => $consultation->id,
                'campos_modificados' => $changedFields,
            ],
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $anamnesis->fresh();
    }

    private function persistAssessmentForms(AnamnesisGeneral $anamnesis, array $forms): void
    {
        $models = [
            'homeopatia' => new AnamnesisHomeopatica,
            'quiropraxia' => new AnamnesisQuiropraxia,
            'acupuntura' => new AnamnesisAcupuntura,
        ];

        foreach ($forms as $formKey => $data) {
            $model = $models[$formKey] ?? null;
            if (! $model || ! is_array($data)) {
                throw ValidationException::withMessages([
                    'assessmentForms' => 'El formulario enviado no corresponde a una evaluación clínica disponible.',
                ]);
            }

            $allowedFields = array_diff($model->getFillable(), ['anamnesis_id']);
            $unexpectedFields = array_diff(array_keys($data), $allowedFields);
            if ($unexpectedFields !== []) {
                throw ValidationException::withMessages([
                    'assessmentForms' => 'El formulario contiene campos no permitidos.',
                ]);
            }

            $existing = $model->newQuery()
                ->where('anamnesis_id', $anamnesis->id)
                ->lockForUpdate()
                ->first();
            if (! $existing && ! collect($data)->contains(fn ($value): bool => match (true) {
                is_bool($value) => $value,
                is_array($value) => $value !== [],
                is_string($value) => trim($value) !== '',
                default => $value !== null,
            })) {
                continue;
            }

            $isNew = $existing === null;
            $record = $existing ?? $model;
            $record->fill($data);
            $record->anamnesis_id = $anamnesis->id;
            $record->save();

            $changedFields = collect(array_keys($data))
                ->filter(fn (string $field): bool => $isNew || $record->wasChanged($field))
                ->values()
                ->all();

            Auditoria::create([
                'user_id' => Auth::id(),
                'tabla_afectada' => $record->getTable(),
                'registro_id' => $record->id,
                'accion' => $isNew ? 'crear' : 'editar',
                'datos_anteriores' => $isNew ? null : [
                    'anamnesis_id' => $anamnesis->id,
                    'campos_modificados' => $changedFields,
                ],
                'datos_nuevos' => [
                    'anamnesis_id' => $anamnesis->id,
                    'campos_modificados' => $changedFields,
                ],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    private function persistAdditionalAssessmentData(
        AnamnesisGeneral $anamnesis,
        string $formKey,
        array $data,
    ): void {
        if (! in_array($formKey, ['homeopatia', 'quiropraxia', 'acupuntura'], true) || $data === []) {
            return;
        }

        $formData = $anamnesis->datos_formulario ?? [];
        $previous = $formData[$formKey] ?? [];
        if (! is_array($previous)) {
            $previous = [];
        }

        $changedFields = collect(array_keys($data))
            ->filter(fn (string $field): bool => ($previous[$field] ?? null) !== $data[$field])
            ->values()
            ->all();

        if ($changedFields === []) {
            return;
        }

        $formData[$formKey] = array_replace($previous, $data);
        $anamnesis->datos_formulario = $formData;
        $anamnesis->save();

        Auditoria::create([
            'user_id' => Auth::id(),
            'tabla_afectada' => 'anamnesis_generales',
            'registro_id' => $anamnesis->id,
            'accion' => 'editar',
            'datos_anteriores' => [
                'consulta_id' => $anamnesis->consulta_id,
                'campos_modificados' => $changedFields,
            ],
            'datos_nuevos' => [
                'consulta_id' => $anamnesis->consulta_id,
                'formulario' => $formKey,
                'campos_modificados' => $changedFields,
            ],
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private function auditTransition(
        Consulta $consultation,
        string $action,
        string $previousState,
        string $newState,
    ): void {
        Auditoria::create([
            'user_id' => Auth::id(),
            'tabla_afectada' => 'consultas',
            'registro_id' => $consultation->id,
            'accion' => 'editar',
            'datos_anteriores' => ['estado' => $previousState],
            'datos_nuevos' => [
                'estado' => $newState,
                'transicion' => $action,
            ],
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function openFromAppointment(int $appointmentId, int $patientId): Consulta
    {
        $expedientId = Cita::query()->whereKey($appointmentId)->value('expediente_id');

        if (! $expedientId) {
            throw ValidationException::withMessages([
                'appointment' => 'La cita seleccionada ya no existe.',
            ]);
        }

        return DB::transaction(function () use ($appointmentId, $patientId, $expedientId): Consulta {
            $expedient = ExpedienteMaestro::query()
                ->whereKey($expedientId)
                ->lockForUpdate()
                ->first();

            $appointment = Cita::query()
                ->whereKey($appointmentId)
                ->lockForUpdate()
                ->first();

            if (! $expedient || ! $appointment || (int) $appointment->expediente_id !== (int) $expedient->id) {
                throw ValidationException::withMessages([
                    'appointment' => 'La cita o su expediente cambiaron. Actualice la ficha e inténtelo de nuevo.',
                ]);
            }

            if ($expedient->paciente_id !== $patientId) {
                throw ValidationException::withMessages([
                    'appointment' => 'La cita no pertenece al paciente de esta ficha.',
                ]);
            }

            if ($appointment->estado !== 'confirmada') {
                throw ValidationException::withMessages([
                    'appointment' => 'Solo se puede iniciar una consulta desde una cita confirmada.',
                ]);
            }

            if (! $appointment->sucursal_id || ! $appointment->profesional_id || ! $appointment->especialidad_id) {
                throw ValidationException::withMessages([
                    'appointment' => 'Asigne sede, profesional y especialidad antes de iniciar la consulta.',
                ]);
            }

            if (Consulta::query()->where('cita_id', $appointment->id)->exists()) {
                throw ValidationException::withMessages([
                    'appointment' => 'Esta cita ya tiene una consulta registrada.',
                ]);
            }

            $consultation = Consulta::create([
                'expediente_id' => $expedient->id,
                'sucursal_id' => $appointment->sucursal_id,
                'especialidad_id' => $appointment->especialidad_id,
                'profesional_id' => $appointment->profesional_id,
                'cita_id' => $appointment->id,
                'fecha_hora' => $appointment->fecha_hora_inicio,
                'estado' => 'abierta',
                'numero_consulta' => Consulta::siguienteNumero(
                    $expedient->id,
                    $appointment->especialidad_id,
                ),
            ]);

            Auditoria::create([
                'user_id' => Auth::id(),
                'tabla_afectada' => 'consultas',
                'registro_id' => $consultation->id,
                'accion' => 'crear',
                'datos_nuevos' => [
                    'expediente_id' => $expedient->id,
                    'cita_id' => $appointment->id,
                    'especialidad_id' => $appointment->especialidad_id,
                    'profesional_id' => $appointment->profesional_id,
                    'numero_consulta' => $consultation->numero_consulta,
                    'estado' => $consultation->estado,
                ],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $consultation;
        });
    }
}
