<?php

namespace Database\Seeders;

use App\Models\Admin\Setting;
use App\Services\ClinicAvailabilityService;
use Illuminate\Database\Seeder;

class HorariosCitasDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        Setting::firstOrCreate(
            ['key' => ClinicAvailabilityService::SETTING_KEY],
            [
                'type' => 'array',
                'value' => ClinicAvailabilityService::DEFAULT_CONFIGURATION,
                'description' => 'Horario global, pausa y cierres para las reservas de la clínica.',
            ],
        );
    }
}
