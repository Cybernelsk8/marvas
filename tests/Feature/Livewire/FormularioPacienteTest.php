<?php

namespace Tests\Feature\Livewire;

use App\Livewire\LandingPage\FormularioPaciente;
use App\Models\Cita;
use App\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class FormularioPacienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_valid_temporary_signature_can_open_the_patient_form(): void
    {
        $cita = $this->crearCita();
        $url = URL::temporarySignedRoute(
            'formulario-paciente',
            now()->addDays(7),
            ['cita' => $cita->id],
        );

        $this->get($url)->assertOk();
        $this->get($url.'&cita=999')->assertForbidden();
        $this->get(URL::temporarySignedRoute(
            'formulario-paciente',
            now()->subMinute(),
            ['cita' => $cita->id],
        ))->assertForbidden();
    }

    public function test_patient_can_complete_and_save_their_information(): void
    {
        $cita = $this->crearCita();

        Livewire::test(FormularioPaciente::class, ['cita' => $cita->id])
            ->set('paciente.nombres', 'Paciente Actualizado')
            ->set('paciente.apellidos', 'De prueba')
            ->set('paciente.telefono', '5555-1234')
            ->set('paciente.numero_dpi', '1234567890123')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSet('guardado', true);

        $this->assertDatabaseHas('pacientes', [
            'id' => $cita->expediente->paciente_id,
            'nombres' => 'Paciente Actualizado',
            'numero_dpi' => '1234567890123',
        ]);
    }

    private function crearCita(): Cita
    {
        $paciente = Paciente::create([
            'nombres' => 'Paciente',
            'apellidos' => 'De prueba',
            'telefono' => '5555-1111',
        ]);
        $expediente = $paciente->expediente()->create();
        $inicio = now()->addDays(3)->setTime(9, 0);

        return $expediente->citas()->create([
            'tipo_cita' => 'primera_vez',
            'estado' => 'solicitada',
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addHour(),
        ]);
    }
}
