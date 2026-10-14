<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Cita;
use App\Models\ExpedienteMaestro;
use App\Models\Paciente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientMergeService
{
    private const PROFILE_FIELDS = [
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'estado_civil',
        'religion',
        'numero_dpi',
        'telefono',
        'telefono_emergencia',
        'email',
        'direccion',
        'ocupacion',
        'como_nos_conocio',
    ];

    private const EXPEDIENTE_FIELDS = [
        'clasificacion',
        'objetivo_principal',
        'fase_terapeutica',
        'estado',
        'notas_generales',
    ];

    private const EXPEDIENTE_TABLES = [
        'citas',
        'consultas',
        'clasificacion_paciente',
        'planes_tratamiento',
        'paquetes_paciente',
        'pagos',
        'archivos_clinicos',
        'consentimientos_registro',
        'notificaciones',
    ];

    public function mergeRequestPatient(int $appointmentId, int $sourcePatientId, int $targetPatientId, array $submittedData): array
    {
        return $this->mergeRecords(
            $appointmentId,
            $sourcePatientId,
            $targetPatientId,
            $submittedData,
            false,
            [],
            [],
        );
    }

    public function previewManualMerge(int $sourcePatientId, int $targetPatientId): array
    {
        [$source, $target] = $this->activePatients($sourcePatientId, $targetPatientId);
        $sourceExpediente = $source->expediente;
        $targetExpediente = $target->expediente;
        $profileConflicts = [];
        $expedienteConflicts = [];

        foreach (self::PROFILE_FIELDS as $field) {
            $sourceValue = $this->normalizeValue($source->getAttribute($field), $field);
            $targetValue = $this->normalizeValue($target->getAttribute($field), $field);

            if ($sourceValue !== null
                && $targetValue !== null
                && $sourceValue !== $targetValue
                && $sourceValue !== 'otro'
                && $targetValue !== 'otro') {
                $profileConflicts[$field] = [
                    'principal' => $targetValue,
                    'origen' => $sourceValue,
                ];
            }
        }

        foreach (self::EXPEDIENTE_FIELDS as $field) {
            if ($field === 'estado') {
                continue;
            }

            $sourceValue = $sourceExpediente?->getAttribute($field);
            $targetValue = $targetExpediente?->getAttribute($field);

            if ($this->isMeaningfulExpedienteValue($field, $sourceValue)
                && $this->isMeaningfulExpedienteValue($field, $targetValue)
                && $sourceValue !== $targetValue) {
                $expedienteConflicts[$field] = [
                    'principal' => $targetValue,
                    'origen' => $sourceValue,
                ];
            }
        }

        $counts = [];
        foreach (self::EXPEDIENTE_TABLES as $table) {
            $counts[$table] = $sourceExpediente
                ? DB::table($table)->where('expediente_id', $sourceExpediente->id)->count()
                : 0;
        }
        $counts['datos_facturacion'] = DB::table('datos_facturacion')
            ->where('paciente_id', $source->id)
            ->count();

        return compact('source', 'target', 'profileConflicts', 'expedienteConflicts', 'counts');
    }

    public function mergeManually(
        int $sourcePatientId,
        int $targetPatientId,
        array $profileChoices,
        array $expedienteChoices,
    ): array {
        return $this->mergeRecords(
            null,
            $sourcePatientId,
            $targetPatientId,
            [],
            true,
            $profileChoices,
            $expedienteChoices,
        );
    }

    private function mergeRecords(
        ?int $appointmentId,
        int $sourcePatientId,
        int $targetPatientId,
        array $submittedData,
        bool $manual,
        array $profileChoices,
        array $expedienteChoices,
    ): array {
        return DB::transaction(function () use ($appointmentId, $sourcePatientId, $targetPatientId, $submittedData, $manual, $profileChoices, $expedienteChoices): array {
            $selectionErrorField = $manual ? 'mergeTargetPatientId' : 'selectedExistingPatientId';

            if ($sourcePatientId === $targetPatientId) {
                throw ValidationException::withMessages([
                    $selectionErrorField => $manual
                        ? 'Selecciona dos pacientes distintos.'
                        : 'Selecciona un paciente distinto al registro de esta solicitud.',
                ]);
            }

            $patients = Paciente::query()
                ->whereKey([$sourcePatientId, $targetPatientId])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $source = $patients->get($sourcePatientId);
            $target = $patients->get($targetPatientId);

            if (! $source || ! $target || ! $source->activo || ! $target->activo || $source->fusionado_en_paciente_id || $target->fusionado_en_paciente_id) {
                throw ValidationException::withMessages([
                    $selectionErrorField => 'Uno de los registros ya no está disponible. Revisa la selección e inténtalo de nuevo.',
                ]);
            }

            $appointment = $appointmentId === null
                ? null
                : Cita::query()->lockForUpdate()->findOrFail($appointmentId);

            if ($appointment && ($appointment->expediente?->paciente_id !== $source->id || ! in_array($appointment->estado, ['solicitada', 'reprogramada'], true))) {
                throw ValidationException::withMessages([
                    'selectedExistingPatientId' => 'La solicitud ya no está pendiente o su paciente cambió.',
                ]);
            }

            if ($appointment) {
                $matches = app(PatientMatchService::class)->matchingCandidates($submittedData, $source->id);

                if (! $matches->contains('id', $target->id)) {
                    throw ValidationException::withMessages([
                        'selectedExistingPatientId' => 'El paciente elegido ya no coincide con los datos de esta solicitud.',
                    ]);
                }
            }

            $sourceExpediente = ExpedienteMaestro::query()
                ->where('paciente_id', $source->id)
                ->lockForUpdate()
                ->first();
            $targetExpediente = ExpedienteMaestro::query()
                ->firstOrCreate(['paciente_id' => $target->id]);
            $targetExpediente = ExpedienteMaestro::query()
                ->whereKey($targetExpediente->id)
                ->lockForUpdate()
                ->firstOrFail();

            $sourceData = $appointment
                ? array_replace($source->only(self::PROFILE_FIELDS), array_intersect_key($submittedData, array_flip(self::PROFILE_FIELDS)))
                : $source->only(self::PROFILE_FIELDS);
            $sourceBefore = $source->only(self::PROFILE_FIELDS);
            $targetBefore = $target->only(self::PROFILE_FIELDS);
            $sourceUpdates = [];
            $profileUpdates = [];
            $profileConflicts = [];

            foreach (self::PROFILE_FIELDS as $field) {
                $incoming = $this->normalizeValue($sourceData[$field] ?? null, $field);
                $current = $this->normalizeValue($target->getAttribute($field), $field);

                if ($incoming === null) {
                    continue;
                }

                if ($field !== 'numero_dpi' || $current === null) {
                    $sourceUpdates[$field] = $incoming;
                }

                if ($current === null || ($field === 'sexo' && $current === 'otro' && $incoming !== 'otro')) {
                    $profileUpdates[$field] = $incoming;
                } elseif ($field === 'sexo' && $incoming === 'otro') {
                    continue;
                } elseif ($current !== $incoming) {
                    $profileConflicts[$field] = [
                        'existente' => $current,
                        ($appointment ? 'solicitud' : 'origen') => $incoming,
                    ];

                    if ($manual) {
                        $choice = $profileChoices[$field] ?? null;

                        if (! in_array($choice, ['source', 'target'], true)) {
                            throw ValidationException::withMessages([
                                'profileChoices.'.$field => 'Elige qué valor conservar para este conflicto.',
                            ]);
                        }

                        if ($choice === 'source') {
                            $profileUpdates[$field] = $incoming;
                        }
                    }
                }
            }

            if ($sourceUpdates !== []) {
                $source->fill($sourceUpdates)->save();
            }

            if ($profileUpdates !== []) {
                if (array_key_exists('numero_dpi', $profileUpdates) && $source->numero_dpi === $profileUpdates['numero_dpi']) {
                    $source->forceFill(['numero_dpi' => null])->save();
                }

                $target->fill($profileUpdates)->save();
            }

            $sourceExpedienteBefore = $sourceExpediente?->only(self::EXPEDIENTE_FIELDS) ?? [];
            $targetExpedienteBefore = $targetExpediente->only(self::EXPEDIENTE_FIELDS);
            $expedienteUpdates = [];
            $expedienteConflicts = [];

            foreach (self::EXPEDIENTE_FIELDS as $field) {
                if ($manual && $field === 'estado') {
                    continue;
                }

                $incoming = $sourceExpediente?->getAttribute($field);
                $current = $targetExpediente->getAttribute($field);
                $default = match ($field) {
                    'clasificacion' => 'sin_clasificar',
                    'fase_terapeutica' => 'sin_definir',
                    'estado' => 'activo',
                    default => null,
                };

                if ($this->isBlank($incoming) || $incoming === $default) {
                    continue;
                }

                if ($this->isBlank($current) || $current === $default) {
                    $expedienteUpdates[$field] = $incoming;
                } elseif ($current !== $incoming) {
                    $expedienteConflicts[$field] = [
                        'existente' => $current,
                        'origen' => $incoming,
                    ];

                    if ($manual) {
                        $choice = $expedienteChoices[$field] ?? null;

                        if (! in_array($choice, ['source', 'target'], true)) {
                            throw ValidationException::withMessages([
                                'expedienteChoices.'.$field => 'Elige qué valor conservar para este conflicto.',
                            ]);
                        }

                        if ($choice === 'source') {
                            $expedienteUpdates[$field] = $incoming;
                        }
                    }
                }
            }

            if ($expedienteUpdates !== []) {
                $targetExpediente->fill($expedienteUpdates)->save();
            }

            $transferred = [];

            foreach (self::EXPEDIENTE_TABLES as $table) {
                $transferred[$table] = DB::table($table)
                    ->when($sourceExpediente, fn ($query) => $query->where('expediente_id', $sourceExpediente->id))
                    ->when(! $sourceExpediente, fn ($query) => $query->whereRaw('1 = 0'))
                    ->update(['expediente_id' => $targetExpediente->id]);
            }

            $transferred['datos_facturacion'] = DB::table('datos_facturacion')
                ->where('paciente_id', $source->id)
                ->update(['paciente_id' => $target->id]);
            $transferred['pacientes_fusionados'] = Paciente::query()
                ->where('fusionado_en_paciente_id', $source->id)
                ->update(['fusionado_en_paciente_id' => $target->id]);

            $source->forceFill([
                'activo' => false,
                'fusionado_en_paciente_id' => $target->id,
            ])->save();
            $sourceExpediente?->forceFill(['estado' => 'inactivo'])->save();

            Auditoria::query()->create([
                'user_id' => Auth::id(),
                'tabla_afectada' => 'pacientes',
                'registro_id' => $source->id,
                'accion' => 'editar',
                'datos_anteriores' => [
                    'perfil' => $sourceBefore,
                    'activo' => true,
                    'fusionado_en_paciente_id' => null,
                    'expediente_id' => $sourceExpediente?->id,
                    'expediente' => $sourceExpedienteBefore,
                ],
                'datos_nuevos' => [
                    'operacion' => $manual ? 'fusion_manual' : 'reutilizar_paciente_desde_solicitud',
                    'perfil' => $source->fresh()->only(self::PROFILE_FIELDS),
                    'datos_solicitud' => $sourceData,
                    'fusionado_en_paciente_id' => $target->id,
                    'cita_id' => $appointment?->id,
                    'perfil_conflictivo' => $profileConflicts,
                    'expediente_conflictivo' => $expedienteConflicts,
                    'resolucion_manual' => $manual ? [
                        'perfil' => $profileChoices,
                        'expediente' => $expedienteChoices,
                    ] : null,
                    'relaciones_trasladadas' => $transferred,
                ],
                'ip' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
            ]);

            Auditoria::query()->create([
                'user_id' => Auth::id(),
                'tabla_afectada' => 'pacientes',
                'registro_id' => $target->id,
                'accion' => 'editar',
                'datos_anteriores' => [
                    'paciente' => $targetBefore,
                    'expediente' => $targetExpedienteBefore,
                ],
                'datos_nuevos' => [
                    'perfil' => $target->fresh()->only(self::PROFILE_FIELDS),
                    'expediente' => $targetExpediente->fresh()->only(self::EXPEDIENTE_FIELDS),
                    'paciente_origen_id' => $source->id,
                    'perfil_conflictivo' => $profileConflicts,
                    'expediente_conflictivo' => $expedienteConflicts,
                    'resolucion_manual' => $manual ? [
                        'perfil' => $profileChoices,
                        'expediente' => $expedienteChoices,
                    ] : null,
                    'relaciones_trasladadas' => $transferred,
                ],
                'ip' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
            ]);

            return [
                'target' => $target->fresh(),
                'profile_updates' => array_keys($profileUpdates),
                'profile_conflicts' => array_keys($profileConflicts),
                'expediente_conflicts' => array_keys($expedienteConflicts),
                'transferred' => $transferred,
            ];
        });
    }

    private function activePatients(int $sourcePatientId, int $targetPatientId): array
    {
        if ($sourcePatientId === $targetPatientId) {
            throw ValidationException::withMessages([
                'mergeTargetPatientId' => 'Selecciona dos pacientes distintos.',
            ]);
        }

        $patients = Paciente::query()
            ->whereKey([$sourcePatientId, $targetPatientId])
            ->with('expediente')
            ->get()
            ->keyBy('id');
        $source = $patients->get($sourcePatientId);
        $target = $patients->get($targetPatientId);

        if (! $source || ! $target || ! $source->activo || ! $target->activo || $source->fusionado_en_paciente_id || $target->fusionado_en_paciente_id) {
            throw ValidationException::withMessages([
                'mergeTargetPatientId' => 'Selecciona dos pacientes activos que no hayan sido fusionados.',
            ]);
        }

        return [$source, $target];
    }

    private function isMeaningfulExpedienteValue(string $field, mixed $value): bool
    {
        $default = match ($field) {
            'clasificacion' => 'sin_clasificar',
            'fase_terapeutica' => 'sin_definir',
            'estado' => 'activo',
            default => null,
        };

        return ! $this->isBlank($value) && $value !== $default;
    }

    private function normalizeValue(mixed $value, string $field): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }

            if ($field === 'email') {
                return mb_strtolower($value);
            }
        }

        return $value;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
