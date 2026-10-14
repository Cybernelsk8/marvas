<?php

namespace Tests\Feature\Livewire;

use App\Livewire\LandingPage\AgendarCita;
use App\Models\Cita;
use App\Models\Paciente;
use Carbon\Carbon;
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
            'fecha' => now()->addDays(3)->format('Y-m-d'),
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

        $fecha = now()->addDays(3)->format('Y-m-d');
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
}
