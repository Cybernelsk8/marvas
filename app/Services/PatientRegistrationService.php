<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Paciente;
use Illuminate\Support\Facades\Auth;

/** Alta de pacientes compartida por el directorio de Pacientes y la creación de citas desde Agenda. */
class PatientRegistrationService
{
    /**
     * Recorta textos, convierte vacíos a null y normaliza nombres y correo.
     * Ej.: ['nombres' => ' ANA ', 'email' => 'A@X.COM'] => nombres 'Ana', email 'a@x.com'.
     */
    public function normalize(array $data): array
    {
        foreach ($data as $field => $value) {
            if (is_string($value)) {
                $data[$field] = trim($value) === '' ? null : trim($value);
            }
        }

        $data['nombres'] = ucwords(mb_strtolower((string) $data['nombres']));
        $data['apellidos'] = ucwords(mb_strtolower((string) $data['apellidos']));
        $data['email'] = filled($data['email'] ?? null) ? mb_strtolower($data['email']) : null;

        return $data;
    }

    /**
     * Crea paciente y expediente y audita el alta. Debe llamarse dentro de una transacción
     * cuando forma parte de otra operación (ej.: crear la cita en el mismo paso).
     */
    public function create(array $normalizedData, ?int $userId = null): Paciente
    {
        $patient = Paciente::query()->create($normalizedData);
        $patient->expediente()->firstOrCreate([]);

        Auditoria::query()->create([
            'user_id' => $userId ?? Auth::id(),
            'tabla_afectada' => 'pacientes',
            'registro_id' => $patient->id,
            'accion' => 'crear',
            'datos_nuevos' => $normalizedData,
            'ip' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
        ]);

        return $patient;
    }
}
