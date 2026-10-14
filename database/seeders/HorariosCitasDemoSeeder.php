<?php

namespace Database\Seeders;

use App\Models\Admin\Setting;
use Illuminate\Database\Seeder;

class HorariosCitasDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        Setting::firstOrCreate(
            ['key' => 'horarios_citas'],
            [
                'type' => 'array',
                'value' => ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'],
                'description' => 'Horarios de demostración para pruebas locales; sustituir por disponibilidad aprobada de la clínica.',
            ],
        );
    }
}
