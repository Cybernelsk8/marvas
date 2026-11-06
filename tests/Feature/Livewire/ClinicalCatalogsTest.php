<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Clinical\Branches;
use App\Livewire\Admin\Clinical\Professionals;
use App\Livewire\Admin\Clinical\Specialties;
use App\Livewire\Admin\Schedule;
use App\Models\Admin\User;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Sucursal;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Seeders\CatalogosClinicosSeeder;
use Database\Seeders\ClinicalCatalogAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClinicalCatalogsTest extends TestCase
{
    use RefreshDatabase;

    private User $sysadmin;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();
        app(CatalogosClinicosSeeder::class)->run();
        Role::firstOrCreate(['name' => 'Sysadmin', 'guard_name' => 'web']);
        app(ClinicalCatalogAccessSeeder::class)->run();

        $this->sysadmin = User::factory()->create();
        $this->sysadmin->assignRole('Sysadmin');
        $this->receptionist = User::factory()->create();
        $this->receptionist->assignRole('Recepcionista');
    }

    public function test_sysadmin_can_manage_branches_and_specialties_without_deleting_history(): void
    {
        Livewire::actingAs($this->sysadmin)
            ->test(Branches::class)
            ->set('nombre', 'Sede nueva')
            ->set('direccion', 'Dirección de prueba')
            ->call('save')
            ->assertHasNoErrors();

        $branch = Sucursal::query()->where('nombre', 'Sede nueva')->firstOrFail();
        Livewire::actingAs($this->sysadmin)
            ->test(Branches::class)
            ->call('toggleActive', $branch->id);
        $this->assertDatabaseHas('sucursales', ['id' => $branch->id, 'activa' => false]);

        Livewire::actingAs($this->sysadmin)
            ->test(Specialties::class)
            ->set('nombre', 'Terapia de prueba')
            ->set('descripcion', 'Descripción')
            ->call('save')
            ->assertHasNoErrors();

        $specialty = Especialidad::query()->where('slug', 'terapia-de-prueba')->firstOrFail();
        Livewire::actingAs($this->sysadmin)
            ->test(Specialties::class)
            ->call('toggleActive', $specialty->id);
        $this->assertDatabaseHas('especialidades', ['id' => $specialty->id, 'activa' => false]);
    }

    public function test_specialty_slug_must_be_unique(): void
    {
        $existing = Especialidad::query()->firstOrFail();

        Livewire::actingAs($this->sysadmin)
            ->test(Specialties::class)
            ->set('nombre', 'Otro nombre')
            ->set('slug', $existing->slug)
            ->call('save')
            ->assertHasErrors('slug');
    }

    public function test_professional_crud_links_an_existing_account_without_assigning_roles(): void
    {
        $user = User::factory()->create();
        $branch = Sucursal::query()->firstOrFail();
        $specialty = Especialidad::query()->where('slug', 'homeopatia')->firstOrFail();

        Livewire::actingAs($this->sysadmin)
            ->test(Professionals::class)
            ->set('userId', (string) $user->id)
            ->set('nombreCompleto', 'Profesional de prueba')
            ->set('branchIds', [(string) $branch->id])
            ->set('primaryBranchId', (string) $branch->id)
            ->set('specialtyIds', [(string) $specialty->id])
            ->set('primarySpecialtyId', (string) $specialty->id)
            ->call('save')
            ->assertHasNoErrors();

        $professional = Profesional::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertDatabaseHas('profesional_sucursales', [
            'profesional_id' => $professional->id,
            'sucursal_id' => $branch->id,
            'es_principal' => true,
        ]);
        $this->assertDatabaseHas('profesional_especialidades', [
            'profesional_id' => $professional->id,
            'especialidad_id' => $specialty->id,
            'es_principal' => true,
        ]);
        $this->assertSame(0, $user->fresh()->roles()->count());

        Livewire::actingAs($this->sysadmin)
            ->test(Professionals::class)
            ->set('search', 'Profesional de prueba')
            ->assertSee('Profesional de prueba')
            ->set('search', $user->email)
            ->assertSee('Profesional de prueba')
            ->call('sort', 'user.email')
            ->assertSee('Profesional de prueba');

        Livewire::actingAs($this->sysadmin)
            ->test(Branches::class)
            ->set('search', $branch->nombre)
            ->assertSee($branch->nombre);
        Livewire::actingAs($this->sysadmin)
            ->test(Specialties::class)
            ->set('search', 'homeopat')
            ->assertSee($specialty->nombre);
    }

    public function test_professional_form_assigns_and_replaces_the_branch_mbi_evaluator(): void
    {
        $branch = Sucursal::query()->firstOrFail();
        $mbi = Especialidad::query()->where('slug', 'evaluacion-mbi')->firstOrFail();
        $other = Especialidad::query()->where('slug', 'homeopatia')->firstOrFail();

        $create = function (string $name, Especialidad $specialty, array $mbiBranches) use ($branch) {
            return Livewire::actingAs($this->sysadmin)
                ->test(Professionals::class)
                ->set('userId', (string) User::factory()->create()->id)
                ->set('nombreCompleto', $name)
                ->set('branchIds', [(string) $branch->id])
                ->set('primaryBranchId', (string) $branch->id)
                ->set('specialtyIds', [(string) $specialty->id])
                ->set('primarySpecialtyId', (string) $specialty->id)
                ->set('mbiBranchIds', $mbiBranches)
                ->call('save');
        };

        $create('No evaluador', $other, [(string) $branch->id])->assertHasErrors('mbiBranchIds');

        $create('Evaluador uno', $mbi, [(string) $branch->id])->assertHasNoErrors();
        $first = Profesional::query()->where('nombre_completo', 'Evaluador uno')->firstOrFail();
        $this->assertDatabaseHas('sucursal_evaluadores_mbi', ['sucursal_id' => $branch->id, 'profesional_id' => $first->id]);

        $create('Evaluador dos', $mbi, [(string) $branch->id])->assertHasNoErrors();
        $second = Profesional::query()->where('nombre_completo', 'Evaluador dos')->firstOrFail();
        $this->assertDatabaseHas('sucursal_evaluadores_mbi', ['sucursal_id' => $branch->id, 'profesional_id' => $second->id]);
        $this->assertDatabaseMissing('sucursal_evaluadores_mbi', ['profesional_id' => $first->id]);
    }

    public function test_reception_can_create_a_confirmed_appointment_for_an_existing_patient(): void
    {
        $branch = Sucursal::query()->firstOrFail();
        $evaluator = $branch->evaluadorMbi()->firstOrFail()->profesional;
        $mbi = Especialidad::query()->where('slug', 'evaluacion-mbi')->firstOrFail();
        $patient = Paciente::create([
            'nombres' => 'Paciente nuevo',
            'apellidos' => 'Agenda',
            'telefono' => '5555-0000',
            'activo' => true,
        ]);
        $patient->expediente()->create();
        $date = $this->nextWeekday();

        $component = Livewire::actingAs($this->receptionist)
            ->test(Schedule::class);
        $component->call('prepareBooking');
        $component
            ->set('bookingPatientId', (string) $patient->id)
            ->set('bookingType', 'primera_vez')
            ->set('bookingDate', $date->toDateString())
            ->set('bookingTime', '09:00')
            ->set('bookingBranchId', (string) $branch->id)
            ->set('bookingProfessionalId', (string) $evaluator->id)
            ->set('bookingSpecialtyId', (string) $mbi->id);
        $component->call('createDirectAppointment')->assertHasNoErrors();

        $this->assertDatabaseHas('citas', [
            'expediente_id' => $patient->expediente->id,
            'sucursal_id' => $branch->id,
            'profesional_id' => $evaluator->id,
            'especialidad_id' => $mbi->id,
            'tipo_cita' => 'primera_vez',
            'estado' => 'confirmada',
            'agendada_por' => $this->receptionist->id,
        ]);
        $this->assertDatabaseHas('auditoria', [
            'tabla_afectada' => 'citas',
            'accion' => 'crear',
            'user_id' => $this->receptionist->id,
        ]);
    }

    public function test_reception_creates_a_new_patient_and_appointment_in_one_step(): void
    {
        $branch = Sucursal::query()->firstOrFail();
        $evaluator = $branch->evaluadorMbi()->firstOrFail()->profesional;
        $mbi = Especialidad::query()->where('slug', 'evaluacion-mbi')->firstOrFail();
        $date = $this->nextWeekday();

        $component = Livewire::actingAs($this->receptionist)->test(Schedule::class);
        $component->call('prepareBooking')
            ->call('setBookingMode', 'nuevo')
            ->set('bookingNewPatient.nombres', 'MARIA')
            ->set('bookingNewPatient.apellidos', 'lópez')
            ->set('bookingNewPatient.telefono', '48840150')
            ->set('bookingNewPatient.email', 'Maria@Example.com')
            ->set('bookingNewPatient.estado_civil', 'Casado')
            ->set('bookingDate', $date->toDateString())
            ->set('bookingTime', '09:00')
            ->set('bookingBranchId', (string) $branch->id)
            ->set('bookingProfessionalId', (string) $evaluator->id)
            ->set('bookingSpecialtyId', (string) $mbi->id)
            ->call('createDirectAppointment')
            ->assertHasNoErrors();

        $patient = Paciente::query()->where('email', 'maria@example.com')->firstOrFail();
        $this->assertSame('Maria López', $patient->nombre_completo);
        $this->assertSame('4884-0150', $patient->telefono);
        $this->assertDatabaseHas('citas', ['expediente_id' => $patient->expediente->id, 'estado' => 'confirmada']);
        $this->assertDatabaseHas('auditoria', ['tabla_afectada' => 'pacientes', 'accion' => 'crear', 'registro_id' => $patient->id]);
    }

    public function test_new_patient_from_schedule_requires_confirming_possible_duplicates_and_rolls_back_on_conflict(): void
    {
        $branch = Sucursal::query()->firstOrFail();
        $evaluator = $branch->evaluadorMbi()->firstOrFail()->profesional;
        $mbi = Especialidad::query()->where('slug', 'evaluacion-mbi')->firstOrFail();
        $date = $this->nextWeekday();
        $existing = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'telefono' => '5555-0000', 'activo' => true]);
        $existing->expediente()->create();

        $fill = fn ($c) => $c->call('prepareBooking')
            ->call('setBookingMode', 'nuevo')
            ->set('bookingNewPatient.nombres', 'Ana')
            ->set('bookingNewPatient.apellidos', 'Pérez')
            ->set('bookingNewPatient.telefono', '5555-0000')
            ->set('bookingNewPatient.email', 'ana2@example.com')
            ->set('bookingDate', $date->toDateString())
            ->set('bookingTime', '09:00')
            ->set('bookingBranchId', (string) $branch->id)
            ->set('bookingProfessionalId', (string) $evaluator->id)
            ->set('bookingSpecialtyId', (string) $mbi->id);

        $component = $fill(Livewire::actingAs($this->receptionist)->test(Schedule::class));
        $component->call('createDirectAppointment')->assertHasErrors('bookingConfirmDistinct');
        $this->assertSame(1, Paciente::query()->count());

        // Ocupa el horario y confirma que es otra persona: el paciente nuevo no debe quedar guardado.
        $start = Carbon::parse($date->toDateString().' 09:00');
        $existing->expediente->citas()->create([
            'fecha_hora_inicio' => $start,
            'fecha_hora_fin' => $start->copy()->addHour(),
            'tipo_cita' => 'seguimiento',
            'estado' => 'confirmada',
        ]);
        $component->set('bookingConfirmDistinct', true)
            ->call('createDirectAppointment')
            ->assertHasErrors('bookingTime');
        $this->assertSame(1, Paciente::query()->count());
    }

    public function test_direct_appointment_rechecks_global_slot_conflicts(): void
    {
        $branch = Sucursal::query()->firstOrFail();
        $evaluator = $branch->evaluadorMbi()->firstOrFail()->profesional;
        $mbi = Especialidad::query()->where('slug', 'evaluacion-mbi')->firstOrFail();
        $date = $this->nextWeekday();
        $occupiedPatient = Paciente::create([
            'nombres' => 'Paciente ocupado',
            'apellidos' => 'Agenda',
            'activo' => true,
        ]);
        $occupiedExpedient = $occupiedPatient->expediente()->create();
        $start = Carbon::parse($date->toDateString().' 09:00');
        $occupiedExpedient->citas()->create([
            'fecha_hora_inicio' => $start,
            'fecha_hora_fin' => $start->copy()->addHour(),
            'tipo_cita' => 'seguimiento',
            'estado' => 'confirmada',
        ]);

        $patient = Paciente::create([
            'nombres' => 'Paciente solicitante',
            'apellidos' => 'Agenda',
            'activo' => true,
        ]);
        $patient->expediente()->create();

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->set('bookingPatientId', (string) $patient->id)
            ->set('bookingType', 'primera_vez')
            ->set('bookingDate', $date->toDateString())
            ->set('bookingTime', '09:00')
            ->set('bookingBranchId', (string) $branch->id)
            ->set('bookingProfessionalId', (string) $evaluator->id)
            ->set('bookingSpecialtyId', (string) $mbi->id)
            ->call('createDirectAppointment')
            ->assertHasErrors('bookingTime');

        $this->assertSame(1, Cita::query()->count());
    }

    public function test_user_without_clinical_catalog_permission_cannot_access_catalogs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/branches')
            ->assertForbidden();
    }

    public function test_sysadmin_can_open_all_catalog_pages(): void
    {
        $this->actingAs($this->sysadmin)->get('/admin/branches')->assertOk();
        $this->actingAs($this->sysadmin)->get('/admin/specialties')->assertOk();
        $this->actingAs($this->sysadmin)->get('/admin/professionals')->assertOk();
    }

    public function test_schedule_viewer_without_booking_permission_cannot_prepare_a_direct_appointment(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('page.view.schedule');

        Livewire::actingAs($user)
            ->test(Schedule::class)
            ->call('prepareBooking')
            ->assertForbidden();
    }

    private function nextWeekday(): CarbonInterface
    {
        $date = today()->addDay();
        while ($date->isWeekend()) {
            $date = $date->addDay();
        }

        return $date;
    }
}
