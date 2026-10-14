<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Patient\Index;
use App\Livewire\Admin\Patient\Show;
use App\Models\Admin\User;
use App\Models\Paciente;
use Database\Seeders\CatalogosClinicosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PatientDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        app(CatalogosClinicosSeeder::class)->run();

        $this->receptionist = User::create([
            'email' => 'patients@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);
        $this->receptionist->givePermissionTo('page.view.patients');
    }

    public function test_patient_directory_searches_full_name_dpi_phone_and_email(): void
    {
        $patient = $this->createPatient([
            'nombres' => 'María Fernanda',
            'apellidos' => 'García López',
            'numero_dpi' => '1234567890101',
            'telefono' => '5555-0123',
            'email' => 'maria@example.test',
        ]);
        $otherPatient = $this->createPatient([
            'nombres' => 'Carlos',
            'apellidos' => 'Pérez',
            'numero_dpi' => '9876543210101',
        ]);

        $this->actingAs($this->receptionist);

        Livewire::test(Index::class)
            ->set('search', 'María Fernanda García')
            ->assertSee($patient->nombre_completo)
            ->assertDontSee($otherPatient->nombre_completo)
            ->set('search', '1234567890101')
            ->assertSee($patient->nombre_completo)
            ->assertDontSee($otherPatient->nombre_completo)
            ->set('search', '55550123')
            ->assertSee($patient->nombre_completo)
            ->assertDontSee($otherPatient->nombre_completo)
            ->set('search', 'maria@example.test')
            ->assertSee($patient->nombre_completo)
            ->assertDontSee($otherPatient->nombre_completo);
    }

    public function test_patient_directory_filters_active_and_inactive_records(): void
    {
        $active = $this->createPatient(['nombres' => 'Paciente', 'apellidos' => 'Activo']);
        $inactive = $this->createPatient([
            'nombres' => 'Paciente',
            'apellidos' => 'Inactivo',
            'activo' => false,
        ]);

        Livewire::actingAs($this->receptionist)
            ->test(Index::class)
            ->assertSee($active->nombre_completo)
            ->assertDontSee($inactive->nombre_completo)
            ->set('status', 'inactive')
            ->assertSee($inactive->nombre_completo)
            ->assertDontSee($active->nombre_completo)
            ->set('status', 'all')
            ->assertSee($active->nombre_completo)
            ->assertSee($inactive->nombre_completo);
    }

    public function test_patient_directory_can_filter_by_expedient_relationship(): void
    {
        $patientWithExpedient = $this->createPatient(['nombres' => 'Con expediente']);
        $expedient = $patientWithExpedient->expediente()->create();
        $patientWithoutExpedient = $this->createPatient(['nombres' => 'Sin expediente']);

        Livewire::actingAs($this->receptionist)
            ->test(Index::class)
            ->set('filters', [[
                'field' => 'expediente.id',
                'operator' => '=',
                'value' => (string) $expedient->id,
            ]])
            ->assertSee($patientWithExpedient->nombre_completo)
            ->assertDontSee($patientWithoutExpedient->nombre_completo);
    }

    public function test_patient_file_shows_basic_information_expedient_summary_and_appointments(): void
    {
        $patient = $this->createPatient([
            'nombres' => 'Lucía',
            'apellidos' => 'Santos',
            'telefono' => '5555-2345',
        ]);
        $expedient = $patient->expediente()->create([
            'clasificacion' => 'atencion_regulacion',
            'objetivo_principal' => 'Mejorar movilidad',
            'fase_terapeutica' => 'regulacion',
        ]);
        $expedient->citas()->create([
            'tipo_cita' => 'seguimiento',
            'estado' => 'confirmada',
            'fecha_hora_inicio' => now()->addDays(2),
            'fecha_hora_fin' => now()->addDays(2)->addHour(),
            'duracion_minutos' => 60,
        ]);

        Livewire::actingAs($this->receptionist)
            ->test(Show::class, ['patient' => $patient])
            ->assertSee('Lucía Santos')
            ->assertSee('5555-2345')
            ->assertSee('Mejorar movilidad')
            ->assertSee('Atencion regulacion')
            ->assertSee('Seguimiento')
            ->assertSee('Confirmada');
    }

    public function test_patient_file_handles_a_patient_without_an_expedient(): void
    {
        $patient = $this->createPatient(['nombres' => 'Sin', 'apellidos' => 'Expediente']);

        Livewire::actingAs($this->receptionist)
            ->test(Show::class, ['patient' => $patient])
            ->assertSee('Sin expediente maestro')
            ->assertSee('No hay citas registradas.');
    }

    public function test_patient_pages_require_the_patient_view_permission(): void
    {
        $patient = $this->createPatient();
        $user = User::create([
            'email' => 'unauthorized-patient@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.patients.index'))
            ->assertForbidden();
        $this->actingAs($user)
            ->get(route('admin.patients.show', $patient))
            ->assertForbidden();

        $this->actingAs($this->receptionist)
            ->get(route('admin.patients.index'))
            ->assertOk();
    }

    private function createPatient(array $attributes = []): Paciente
    {
        static $sequence = 0;
        $sequence++;

        return Paciente::create(array_merge([
            'nombres' => 'Paciente '.$sequence,
            'apellidos' => 'Prueba',
            'telefono' => '5555-000'.$sequence,
        ], $attributes));
    }
}
