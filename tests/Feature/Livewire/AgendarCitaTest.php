<?php

namespace Tests\Feature\Livewire;

use App\Livewire\LandingPage\AgendarCita;
use App\Models\Admin\Setting;
use App\Models\Cita;
use App\Models\Paciente;
use App\Services\ClinicAvailabilityService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Seeders\CatalogosClinicosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithLivewireHttp;
use Tests\TestCase;

class AgendarCitaTest extends TestCase
{
    use InteractsWithLivewireHttp;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(CatalogosClinicosSeeder::class)->run();
        Livewire::component('landing-page.agendar-cita', AgendarCita::class);
    }

    public function test_public_booking_creates_a_pending_request_with_the_message_for_reception(): void
    {
        $page = $this->get('/agendar-cita')->assertOk();
        $snapshot = $this->componentSnapshot($page, 'landing-page.agendar-cita');

        $response = $this->updateLivewireComponent($snapshot, [
            'tipo' => 'primera_vez',
            'nombres' => 'Paciente',
            'apellidos' => 'De prueba',
            'telefono' => '5555-1111',
            'email' => 'paciente@example.test',
            'mensaje' => 'Solicito una primera evaluación.',
            'fecha' => $this->nextWeekday()->format('Y-m-d'),
            'hora' => '09:00',
        ], [['store']]);

        $response->assertOk();

        $this->assertDatabaseCount('pacientes', 1);
        $this->assertDatabaseCount('expediente_maestro', 1);
        $this->assertDatabaseHas('citas', [
            'tipo_cita' => 'primera_vez',
            'estado' => 'solicitada',
            'notas_recepcion' => 'Solicito una primera evaluación.',
        ]);

        $this->assertSame('Paciente De Prueba', Cita::query()->firstOrFail()->expediente->paciente->nombre_completo);
    }

    public function test_public_booking_does_not_create_a_patient_if_the_slot_was_taken(): void
    {
        $this->assertDatabaseCount('pacientes', 0);

        $fecha = $this->nextWeekday()->format('Y-m-d');
        $this->crearCitaOcupada($fecha, '09:00');
        $snapshot = $this->componentSnapshot(
            $this->get('/agendar-cita')->assertOk(),
            'landing-page.agendar-cita',
        );

        $this->updateLivewireComponent($snapshot, [
            'tipo' => 'primera_vez',
            'nombres' => 'Paciente',
            'apellidos' => 'De prueba',
            'telefono' => '5555-1111',
            'fecha' => $fecha,
            'hora' => '09:00',
        ], [['store']])->assertOk();

        $this->assertDatabaseCount('pacientes', 1);
        $this->assertDatabaseCount('citas', 1);
    }

    public function test_public_booking_rejects_a_date_inside_an_exceptional_closure(): void
    {
        $fecha = $this->nextWeekday()->addWeek()->toDateString();
        $configuration = ClinicAvailabilityService::DEFAULT_CONFIGURATION;
        $configuration['closures'] = [[
            'start_date' => $fecha,
            'end_date' => $fecha,
            'reason' => 'Feriado',
        ]];
        Setting::query()->updateOrCreate(
            ['key' => ClinicAvailabilityService::SETTING_KEY],
            ['type' => 'array', 'value' => $configuration],
        );

        Livewire::test(AgendarCita::class)
            ->set('tipo', 'primera_vez')
            ->set('nombres', 'Paciente')
            ->set('apellidos', 'De prueba')
            ->set('telefono', '5555-1111')
            ->set('fecha', $fecha)
            ->set('hora', '09:00')
            ->call('store')
            ->assertHasErrors('hora');

        $this->assertDatabaseCount('pacientes', 0);
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_public_booking_uses_custom_weekend_hours_and_keeps_unconfigured_days_closed(): void
    {
        $saturday = today()->next(Carbon::SATURDAY)->toDateString();
        $sunday = today()->next(Carbon::SUNDAY)->toDateString();
        $configuration = [
            'groups' => [
                ['days' => [6], 'hours' => ['09:30']],
            ],
            'duration_minutes' => 60,
            'closures' => [],
        ];
        Setting::query()->updateOrCreate(
            ['key' => ClinicAvailabilityService::SETTING_KEY],
            ['type' => 'array', 'value' => $configuration],
        );

        Livewire::test(AgendarCita::class)
            ->set('tipo', 'primera_vez')
            ->set('nombres', 'Paciente')
            ->set('apellidos', 'Sábado')
            ->set('telefono', '5555-1111')
            ->set('fecha', $saturday)
            ->set('hora', '09:30')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('citas', [
            'fecha_hora_inicio' => "{$saturday} 09:30:00",
            'duracion_minutos' => 60,
        ]);

        Livewire::test(AgendarCita::class)
            ->set('tipo', 'primera_vez')
            ->set('nombres', 'Paciente')
            ->set('apellidos', 'Domingo')
            ->set('telefono', '5555-2222')
            ->set('fecha', $sunday)
            ->set('hora', '09:30')
            ->call('store')
            ->assertHasErrors('hora');

        $this->assertDatabaseCount('pacientes', 1);
        $this->assertDatabaseCount('citas', 1);
    }

    public function test_public_booking_uses_the_configured_appointment_duration(): void
    {
        $date = $this->nextWeekday()->addWeek()->toDateString();
        $configuration = [
            'groups' => [
                ['days' => [1, 2, 3, 4, 5], 'hours' => ['08:00', '10:00', '13:00', '15:00']],
            ],
            'duration_minutes' => 120,
            'closures' => [],
        ];
        Setting::query()->updateOrCreate(
            ['key' => ClinicAvailabilityService::SETTING_KEY],
            ['type' => 'array', 'value' => $configuration],
        );

        Livewire::test(AgendarCita::class)
            ->set('tipo', 'primera_vez')
            ->set('nombres', 'Paciente')
            ->set('apellidos', 'Dos horas')
            ->set('telefono', '5555-3333')
            ->set('fecha', $date)
            ->set('hora', '08:00')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('citas', [
            'fecha_hora_inicio' => "{$date} 08:00:00",
            'fecha_hora_fin' => "{$date} 10:00:00",
            'duracion_minutos' => 120,
        ]);
    }

    private function crearCitaOcupada(string $fecha, string $hora): void
    {
        $paciente = Paciente::create([
            'nombres' => 'Paciente existente',
            'apellidos' => 'De prueba',
            'telefono' => '5555-2222',
        ]);
        $expediente = $paciente->expediente()->create();
        $inicio = Carbon::parse("{$fecha} {$hora}");

        $expediente->citas()->create([
            'tipo_cita' => 'primera_vez',
            'estado' => 'confirmada',
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addHour(),
        ]);
    }

    private function nextWeekday(): CarbonInterface
    {
        $date = today()->addDay();

        while ($date->isWeekend()) {
            $date->addDay();
        }

        return $date;
    }
}
