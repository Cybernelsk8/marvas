<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Sucursal;
use App\Models\SucursalEvaluadorMbi;
use Illuminate\Database\Seeder;

class EvaluadoresMbiDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $sucursal = Sucursal::query()
            ->where('nombre', 'Sede de demostración MARVAS')
            ->first();
        $especialidad = Especialidad::query()->where('slug', 'evaluacion-mbi')->first();

        if (! $sucursal || ! $especialidad) {
            return;
        }

        $profesional = Profesional::query()
            ->where('nombre_completo', ProfesionalesDemoSeeder::MBI_EVALUATOR_NAME)
            ->where('activo', true)
            ->whereHas('sucursales', fn ($query) => $query->whereKey($sucursal->id))
            ->whereHas('especialidades', fn ($query) => $query->whereKey($especialidad->id))
            ->first();

        if ($profesional) {
            SucursalEvaluadorMbi::query()->firstOrCreate(
                ['sucursal_id' => $sucursal->id],
                ['profesional_id' => $profesional->id],
            );
        }
    }
}
