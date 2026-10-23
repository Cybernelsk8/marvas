<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use Illuminate\Database\Seeder;

class EspecialidadesSeeder extends Seeder
{
    public function run(): void
    {
        $especialidades = [
            [
                'nombre' => 'Homeopatía',
                'slug' => 'homeopatia',
                'descripcion' => 'Atención homeopática dentro del modelo integrativo de MARVAS.',
                'color_hex' => '#8B5CF6',
            ],
            [
                'nombre' => 'Naturopatía',
                'slug' => 'naturopatia',
                'descripcion' => 'Atención naturopática dentro del modelo integrativo de MARVAS.',
                'color_hex' => '#16A34A',
            ],
            [
                'nombre' => 'Quiropraxia',
                'slug' => 'quiropraxia',
                'descripcion' => 'Atención quiropráctica y musculoesquelética.',
                'color_hex' => '#2563EB',
            ],
            [
                'nombre' => 'Masaje muscular',
                'slug' => 'masaje-muscular',
                'descripcion' => 'Sesiones de masaje muscular.',
                'color_hex' => '#F97316',
            ],
            [
                'nombre' => 'Acupuntura',
                'slug' => 'acupuntura',
                'descripcion' => 'Atención de acupuntura.',
                'color_hex' => '#DC2626',
            ],
            [
                'nombre' => 'Iridología',
                'slug' => 'iridologia',
                'descripcion' => 'Evaluación de iridología.',
                'color_hex' => '#0891B2',
            ],
            [
                'nombre' => 'Evaluación MBI',
                'slug' => 'evaluacion-mbi',
                'descripcion' => 'Evaluación MBI inicial realizada por el evaluador de la sede.',
                'color_hex' => '#0F766E',
            ],
        ];

        foreach ($especialidades as $especialidad) {
            Especialidad::updateOrCreate(
                ['slug' => $especialidad['slug']],
                [...$especialidad, 'activa' => true],
            );
        }
    }
}
