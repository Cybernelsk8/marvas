<?php

namespace App\Services;

use App\Models\Paciente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PatientMatchService
{
    public function matchingCandidates(array $data, ?int $ignorePatientId = null): Collection
    {
        $dpi = $this->digits($data['numero_dpi'] ?? null);
        $phone = $this->digits($data['telefono'] ?? null);
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $firstName = mb_strtolower(trim((string) ($data['nombres'] ?? '')));
        $lastName = mb_strtolower(trim((string) ($data['apellidos'] ?? '')));

        if ($dpi === '' && $phone === '' && $email === '' && ($firstName === '' || $lastName === '')) {
            return new Collection;
        }

        return Paciente::query()
            ->where('activo', true)
            ->whereNull('fusionado_en_paciente_id')
            ->whereKeyNot($ignorePatientId)
            ->where(function (Builder $query) use ($dpi, $phone, $email, $firstName, $lastName): void {
                if ($dpi !== '') {
                    $query->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(COALESCE(numero_dpi, ''), '-', ''), ' ', ''), '.', '') = ?",
                        [$dpi],
                    );
                }

                if ($phone !== '') {
                    $query->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telefono, ''), '-', ''), ' ', ''), '+', ''), '(', ''), ')', '') = ?",
                        [$phone],
                    );
                }

                if ($email !== '') {
                    $query->orWhereRaw('LOWER(email) = ?', [$email]);
                }

                if ($firstName !== '' && $lastName !== '') {
                    $query->orWhere(function (Builder $nameQuery) use ($firstName, $lastName): void {
                        $nameQuery->whereRaw('LOWER(nombres) LIKE ?', ['%'.$firstName.'%'])
                            ->whereRaw('LOWER(apellidos) LIKE ?', ['%'.$lastName.'%']);
                    });
                }
            })
            ->with('expediente')
            ->orderByDesc('activo')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->limit(10)
            ->get();
    }

    private function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value);
    }
}
