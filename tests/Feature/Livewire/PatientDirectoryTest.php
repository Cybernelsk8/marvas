<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Patient\Index;
use App\Livewire\Admin\Patient\Show;
use App\Models\Admin\User;
use App\Models\ArchivoClinico;
use App\Models\Auditoria;
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
        $this->receptionist->givePermissionTo([
            'page.view.patients',
            'patients.create',
            'patients.edit',
            'patients.merge',
        ]);
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
            ->assertDontSee('Mejorar movilidad')
            ->assertDontSee('Atencion regulacion')
            ->assertSee('Seguimiento')
            ->assertSee('Confirmada');
    }

    public function test_patient_file_sections_are_visible_only_with_their_specific_permissions(): void
    {
        $patient = $this->createPatient();
        $expedient = $patient->expediente()->create([
            'clasificacion' => 'atencion_regulacion',
            'objetivo_principal' => 'sensitive-clinical-goal',
            'fase_terapeutica' => 'regulacion',
        ]);
        ArchivoClinico::create([
            'expediente_id' => $expedient->id,
            'subido_por' => $this->receptionist->id,
            'tipo_archivo' => 'documento',
            'nombre_original' => 'patient-document.pdf',
            'path_almacenamiento' => 'private/sensitive-storage-path',
            'mime_type' => 'application/pdf',
        ]);

        Livewire::actingAs($this->receptionist)
            ->test(Show::class, ['patient' => $patient])
            ->assertDontSee('Atencion regulacion')
            ->assertDontSee('sensitive-clinical-goal')
            ->assertDontSee('Información clínica')
            ->assertDontSee('Documentos y consentimientos')
            ->assertDontSee('Pagos y paquetes')
            ->assertDontSee('patient-document.pdf')
            ->assertDontSee('sensitive-storage-path');

        $this->receptionist->givePermissionTo('patients.clinical.view');

        Livewire::actingAs($this->receptionist)
            ->test(Show::class, ['patient' => $patient])
            ->assertSee('Atencion regulacion')
            ->assertSee('sensitive-clinical-goal')
            ->assertSee('Información clínica')
            ->assertDontSee('Documentos y consentimientos')
            ->assertDontSee('Pagos y paquetes');

        $this->receptionist->givePermissionTo('patients.documents.view');

        Livewire::actingAs($this->receptionist)
            ->test(Show::class, ['patient' => $patient])
            ->assertSee('Documentos y consentimientos')
            ->assertSee('patient-document.pdf')
            ->assertDontSee('Pagos y paquetes')
            ->assertDontSee('sensitive-storage-path');

        $this->receptionist->givePermissionTo('patients.billing.view');

        Livewire::actingAs($this->receptionist)
            ->test(Show::class, ['patient' => $patient])
            ->assertSee('Pagos y paquetes')
            ->assertDontSee('sensitive-storage-path');
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

    public function test_creating_patient_with_possible_matches_requires_an_explicit_confirmation(): void
    {
        $existing = $this->createPatient([
            'nombres' => 'Ana María',
            'apellidos' => 'López Pérez',
            'telefono' => '+502 5555-9090',
            'email' => 'ana@example.test',
        ]);

        Livewire::actingAs($this->receptionist)
            ->test(Index::class)
            ->call('createPatient')
            ->set('patientForm.nombres', 'Ana María')
            ->set('patientForm.apellidos', 'López Pérez')
            ->set('patientForm.telefono', '55559090')
            ->set('patientForm.email', 'ana@example.test')
            ->assertSee($existing->nombre_completo)
            ->call('savePatient')
            ->assertHasErrors('confirmDistinctPatient');

        $this->assertDatabaseCount('pacientes', 1);
        $this->assertDatabaseCount('expediente_maestro', 0);
    }

    public function test_patient_can_be_created_with_a_unique_expedient_and_audit_entry(): void
    {
        Livewire::actingAs($this->receptionist)
            ->test(Index::class)
            ->call('createPatient')
            ->set('patientForm.nombres', '  MARIA  ')
            ->set('patientForm.apellidos', '  GARCIA LOPEZ ')
            ->set('patientForm.numero_dpi', '1234567890101')
            ->set('patientForm.email', 'MARIA@EXAMPLE.TEST')
            ->call('savePatient')
            ->assertHasNoErrors();

        $patient = Paciente::query()->firstOrFail();
        $this->assertSame('Maria', $patient->nombres);
        $this->assertSame('Garcia Lopez', $patient->apellidos);
        $this->assertSame('maria@example.test', $patient->email);
        $this->assertDatabaseCount('expediente_maestro', 1);
        $this->assertDatabaseHas('expediente_maestro', ['paciente_id' => $patient->id]);
        $this->assertDatabaseHas('auditoria', [
            'user_id' => $this->receptionist->id,
            'tabla_afectada' => 'pacientes',
            'registro_id' => $patient->id,
            'accion' => 'crear',
        ]);
    }

    public function test_user_can_explicitly_confirm_creation_even_when_a_possible_match_exists(): void
    {
        $this->createPatient([
            'nombres' => 'Juan',
            'apellidos' => 'Gómez',
            'numero_dpi' => '1234567890101',
        ]);

        Livewire::actingAs($this->receptionist)
            ->test(Index::class)
            ->call('createPatient')
            ->set('patientForm.nombres', 'Juan')
            ->set('patientForm.apellidos', 'Gómez')
            ->set('patientForm.numero_dpi', '1234567890102')
            ->set('confirmDistinctPatient', true)
            ->call('savePatient')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('pacientes', 2);
        $this->assertDatabaseCount('expediente_maestro', 1);
    }

    public function test_patient_can_be_edited_with_audit_and_without_duplicate_expedients(): void
    {
        $patient = $this->createPatient([
            'nombres' => 'Elena',
            'apellidos' => 'Ruiz',
        ]);
        $expedient = $patient->expediente()->create();

        Livewire::actingAs($this->receptionist)
            ->test(Index::class)
            ->call('editPatient', $patient->id)
            ->set('patientForm.telefono', '5555-5678')
            ->set('patientForm.email', 'elena@example.test')
            ->call('savePatient')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('expediente_maestro', 1);
        $this->assertDatabaseHas('pacientes', [
            'id' => $patient->id,
            'telefono' => '5555-5678',
            'email' => 'elena@example.test',
        ]);
        $this->assertDatabaseHas('auditoria', [
            'user_id' => $this->receptionist->id,
            'tabla_afectada' => 'pacientes',
            'registro_id' => $patient->id,
            'accion' => 'editar',
        ]);
        $this->assertDatabaseHas('expediente_maestro', ['id' => $expedient->id, 'paciente_id' => $patient->id]);
    }

    public function test_patient_merge_previews_conflicts_and_requires_an_explicit_choice_for_each(): void
    {
        $source = $this->createPatient([
            'nombres' => 'María',
            'apellidos' => 'García',
            'telefono' => '5555-1234',
            'email' => 'origen@example.test',
            'direccion' => 'Dirección de origen',
            'telefono_emergencia' => '5555-9999',
        ]);
        $sourceExpedient = $source->expediente()->create([
            'objetivo_principal' => 'Objetivo del origen',
            'notas_generales' => 'Notas del origen',
            'estado' => 'inactivo',
        ]);
        $sourceAppointment = $sourceExpedient->citas()->create([
            'tipo_cita' => 'primera_vez',
            'estado' => 'solicitada',
            'fecha_hora_inicio' => now()->addDays(3)->setTime(9, 0),
            'fecha_hora_fin' => now()->addDays(3)->setTime(10, 0),
            'duracion_minutos' => 60,
        ]);
        $source->datosFacturacion()->create([
            'nit' => '1234567-8',
            'nombre_razon_social' => 'María García',
        ]);

        $target = $this->createPatient([
            'nombres' => 'María',
            'apellidos' => 'García',
            'telefono' => '5555-1234',
            'email' => 'principal@example.test',
            'direccion' => 'Dirección principal',
        ]);
        $targetExpedient = $target->expediente()->create([
            'objetivo_principal' => 'Objetivo principal',
            'notas_generales' => 'Notas principales',
        ]);

        Livewire::actingAs($this->receptionist)
            ->test(Index::class)
            ->call('startPatientMerge', $source->id)
            ->assertSee('Fusionar expediente')
            ->set('mergeTargetSearch', 'María García')
            ->assertSee($target->nombre_completo)
            ->call('selectMergeTarget', $target->id)
            ->assertSee('Dirección de origen')
            ->assertSee('Dirección principal')
            ->call('mergePatientsManually')
            ->assertHasErrors([
                'confirmPatientMerge',
                'mergeProfileChoices.email',
                'mergeProfileChoices.direccion',
                'mergeExpedienteChoices.objetivo_principal',
                'mergeExpedienteChoices.notas_generales',
            ]);

        $this->assertDatabaseHas('citas', [
            'id' => $sourceAppointment->id,
            'expediente_id' => $sourceExpedient->id,
        ]);
        $this->assertDatabaseHas('pacientes', [
            'id' => $source->id,
            'activo' => true,
            'fusionado_en_paciente_id' => null,
        ]);

        Livewire::actingAs($this->receptionist)
            ->test(Index::class)
            ->call('startPatientMerge', $source->id)
            ->set('mergeTargetSearch', 'María García')
            ->call('selectMergeTarget', $target->id)
            ->set('mergeProfileChoices.email', 'source')
            ->set('mergeProfileChoices.direccion', 'target')
            ->set('mergeExpedienteChoices.objetivo_principal', 'source')
            ->set('mergeExpedienteChoices.notas_generales', 'target')
            ->set('confirmPatientMerge', true)
            ->call('mergePatientsManually')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pacientes', [
            'id' => $source->id,
            'activo' => false,
            'fusionado_en_paciente_id' => $target->id,
        ]);
        $this->assertDatabaseHas('pacientes', [
            'id' => $target->id,
            'email' => 'origen@example.test',
            'direccion' => 'Dirección principal',
            'telefono_emergencia' => '5555-9999',
        ]);
        $this->assertDatabaseHas('expediente_maestro', [
            'id' => $sourceExpedient->id,
            'estado' => 'inactivo',
        ]);
        $this->assertDatabaseHas('expediente_maestro', [
            'id' => $targetExpedient->id,
            'objetivo_principal' => 'Objetivo del origen',
            'notas_generales' => 'Notas principales',
            'estado' => 'activo',
        ]);
        $this->assertDatabaseHas('citas', [
            'id' => $sourceAppointment->id,
            'expediente_id' => $targetExpedient->id,
        ]);
        $this->assertDatabaseHas('datos_facturacion', [
            'paciente_id' => $target->id,
            'nit' => '1234567-8',
        ]);
        $this->assertDatabaseHas('auditoria', [
            'tabla_afectada' => 'pacientes',
            'registro_id' => $source->id,
            'accion' => 'editar',
        ]);
        $mergeAudit = Auditoria::query()
            ->where('tabla_afectada', 'pacientes')
            ->where('registro_id', $source->id)
            ->latest('id')
            ->firstOrFail();
        $this->assertSame('source', $mergeAudit->datos_nuevos['resolucion_manual']['perfil']['email']);
        $this->assertSame('target', $mergeAudit->datos_nuevos['resolucion_manual']['expediente']['notas_generales']);
    }

    public function test_patient_merge_requires_its_specific_permission(): void
    {
        $source = $this->createPatient();
        $viewer = User::create([
            'email' => 'patient-merge-viewer@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);
        $viewer->givePermissionTo('page.view.patients');

        Livewire::actingAs($viewer)
            ->test(Index::class)
            ->assertDontSee('Fusionar expediente')
            ->call('startPatientMerge', $source->id)
            ->assertForbidden();
    }

    public function test_patient_creation_requires_its_specific_permission(): void
    {
        $viewer = User::create([
            'email' => 'patient-viewer@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);
        $viewer->givePermissionTo('page.view.patients');

        Livewire::actingAs($viewer)
            ->test(Index::class)
            ->call('createPatient')
            ->assertForbidden();
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
