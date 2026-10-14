<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Patient\Show;
use App\Livewire\Admin\Consultation\Show as ConsultationForm;
use App\Models\Auditoria;
use App\Models\Admin\User;
use App\Models\AnamnesisGeneral;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Sucursal;
use App\Services\ConsultationService;
use Database\Seeders\CatalogosClinicosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsultationTest extends TestCase
{
    use RefreshDatabase;

    private User $clinician;

    protected function setUp(): void
    {
        parent::setUp();

        app(CatalogosClinicosSeeder::class)->run();

        $this->clinician = User::create([
            'email' => 'clinician@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);
        $this->clinician->givePermissionTo([
            'page.view.patients',
            'patients.clinical.view',
            'consultations.create',
        ]);
    }

    public function test_authorized_user_can_open_a_consultation_from_a_confirmed_appointment(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();

        Livewire::actingAs($this->clinician)
            ->test(Show::class, ['patient' => $patient])
            ->assertSee('Iniciar consulta')
            ->call('startConsultation', $appointment->id)
            ->assertSee('Consulta registrada');

        $this->assertDatabaseHas('consultas', [
            'expediente_id' => $appointment->expediente_id,
            'sucursal_id' => $appointment->sucursal_id,
            'especialidad_id' => $appointment->especialidad_id,
            'profesional_id' => $appointment->profesional_id,
            'cita_id' => $appointment->id,
            'fecha_hora' => $appointment->fecha_hora_inicio,
            'estado' => 'abierta',
            'numero_consulta' => 1,
        ]);
        $this->assertDatabaseHas('citas', [
            'id' => $appointment->id,
            'estado' => 'confirmada',
        ]);
        $this->assertDatabaseHas('auditoria', [
            'tabla_afectada' => 'consultas',
            'accion' => 'crear',
            'user_id' => $this->clinician->id,
        ]);
    }

    public function test_consultation_number_increments_per_expedient_and_specialty(): void
    {
        [$patient, $firstAppointment, $specialist, $specialty, $branch] = $this->createConfirmedAppointment();
        $secondAppointment = $this->createAppointment(
            $patient,
            $branch,
            $specialist,
            $specialty,
            'confirmada',
        );
        $otherSpecialty = Especialidad::query()->where('slug', 'naturopatia')->firstOrFail();
        $otherSpecialist = Profesional::query()
            ->whereHas('especialidades', fn ($query) => $query->whereKey($otherSpecialty->id))
            ->firstOrFail();
        $otherBranch = $otherSpecialist->sucursales()->firstOrFail();
        $thirdAppointment = $this->createAppointment(
            $patient,
            $otherBranch,
            $otherSpecialist,
            $otherSpecialty,
            'confirmada',
        );

        Livewire::actingAs($this->clinician)
            ->test(Show::class, ['patient' => $patient])
            ->call('startConsultation', $firstAppointment->id)
            ->call('startConsultation', $secondAppointment->id)
            ->call('startConsultation', $thirdAppointment->id);

        $this->assertSame(
            [1, 2],
            Consulta::query()
                ->where('expediente_id', $patient->expediente->id)
                ->where('especialidad_id', $specialty->id)
                ->orderBy('numero_consulta')
                ->pluck('numero_consulta')
                ->all(),
        );
        $this->assertDatabaseHas('consultas', [
            'cita_id' => $thirdAppointment->id,
            'numero_consulta' => 1,
        ]);
    }

    public function test_non_confirmed_appointments_cannot_start_consultations(): void
    {
        [$patient, $appointment, $specialist, $specialty, $branch] = $this->createConfirmedAppointment();
        $appointment->update(['estado' => 'solicitada']);

        Livewire::actingAs($this->clinician)
            ->test(Show::class, ['patient' => $patient])
            ->call('startConsultation', $appointment->id)
            ->assertHasErrors('appointment');

        $this->assertDatabaseCount('consultas', 0);
    }

    public function test_user_without_consultation_permission_cannot_open_a_consultation(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $viewer = User::create([
            'email' => 'clinical-viewer@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);
        $viewer->givePermissionTo(['page.view.patients', 'patients.clinical.view']);

        Livewire::actingAs($viewer)
            ->test(Show::class, ['patient' => $patient])
            ->assertDontSee('Iniciar consulta')
            ->call('startConsultation', $appointment->id)
            ->assertForbidden();

        $this->assertDatabaseCount('consultas', 0);
    }

    public function test_authorized_user_can_save_and_update_general_anamnesis_without_writing_generated_bmi(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->set('form.motivo_consulta', 'Dolor de prueba')
            ->set('form.historia_padecimiento', 'Comenzó hace dos días')
            ->set('form.antecedentes_patologicos', "Asma\nHipertensión")
            ->set('form.habitos', "Camina diariamente\nNo fuma")
            ->set('form.presion_arterial', '120/80')
            ->set('form.frecuencia_cardiaca', '72')
            ->set('form.temperatura', '36.5')
            ->set('form.peso_kg', '70')
            ->set('form.talla_cm', '170')
            ->set('form.observaciones_profesional', 'Observación inicial')
            ->set('assessmentForms.homeopatia.aspecto_paciente', 'Aspecto sereno')
            ->set('assessmentForms.homeopatia.sintomas_mentales', 'Sueño inquieto')
            ->set('assessmentForms.quiropraxia.diagnostico', 'Sin hallazgos musculoesqueléticos')
            ->set('assessmentForms.acupuntura.diagnostico_energetico', 'Qi equilibrado')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('24.22')
            ->assertSee('Evaluaciones del paciente')
            ->assertSee('Evaluación homeopática')
            ->assertSee('Evaluación quiropráctica')
            ->assertSee('Evaluación de acupuntura');

        $anamnesis = AnamnesisGeneral::query()->where('consulta_id', $consultation->id)->firstOrFail();
        $this->assertSame('Dolor de prueba', $anamnesis->motivo_consulta);
        $this->assertSame(['Asma', 'Hipertensión'], $anamnesis->antecedentes_patologicos);
        $this->assertSame(['Camina diariamente', 'No fuma'], $anamnesis->habitos);
        $this->assertEquals(24.22, (float) $anamnesis->imc);
        $this->assertDatabaseHas('anamnesis_homeopatica', [
            'anamnesis_id' => $anamnesis->id,
            'aspecto_paciente' => 'Aspecto sereno',
            'sintomas_mentales' => 'Sueño inquieto',
        ]);
        $this->assertDatabaseHas('anamnesis_quiropraxia', [
            'anamnesis_id' => $anamnesis->id,
            'diagnostico' => 'Sin hallazgos musculoesqueléticos',
        ]);
        $this->assertDatabaseHas('anamnesis_acupuntura', [
            'anamnesis_id' => $anamnesis->id,
            'diagnostico_energetico' => 'Qi equilibrado',
        ]);
        $this->assertDatabaseHas('auditoria', [
            'tabla_afectada' => 'anamnesis_generales',
            'registro_id' => $anamnesis->id,
            'accion' => 'crear',
            'user_id' => $this->clinician->id,
        ]);

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation->fresh()])
            ->assertSet('form.antecedentes_patologicos', implode(PHP_EOL, ['Asma', 'Hipertensión']))
            ->assertSet('assessmentForms.homeopatia.aspecto_paciente', 'Aspecto sereno')
            ->assertSet('assessmentForms.quiropraxia.diagnostico', 'Sin hallazgos musculoesqueléticos')
            ->assertSet('assessmentForms.acupuntura.diagnostico_energetico', 'Qi equilibrado')
            ->set('form.motivo_consulta', 'Dolor actualizado')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, AnamnesisGeneral::query()->where('consulta_id', $consultation->id)->count());
        $this->assertDatabaseHas('anamnesis_generales', [
            'consulta_id' => $consultation->id,
            'motivo_consulta' => 'Dolor actualizado',
        ]);
        $this->assertDatabaseHas('auditoria', [
            'tabla_afectada' => 'anamnesis_generales',
            'registro_id' => $anamnesis->id,
            'accion' => 'editar',
            'user_id' => $this->clinician->id,
        ]);
    }

    public function test_chiropractic_anamnesis_persists_boolean_enum_and_optional_details(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment('homeopatia');
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->set('form.motivo_consulta', 'Dolor lumbar')
            ->set('assessmentForms.quiropraxia.implantes_metales', true)
            ->set('assessmentForms.quiropraxia.embarazo', true)
            ->set('assessmentForms.quiropraxia.semanas_gestacion', '20')
            ->set('assessmentForms.quiropraxia.ciatica', true)
            ->set('assessmentForms.quiropraxia.calidad_sueno', 'moderado')
            ->set('assessmentForms.quiropraxia.desplazamiento', 'ligero')
            ->set('assessmentForms.quiropraxia.diagnostico', 'Limitación de movilidad lumbar')
            ->call('save')
            ->assertHasNoErrors();

        $anamnesis = AnamnesisGeneral::query()->where('consulta_id', $consultation->id)->firstOrFail();
        $this->assertDatabaseHas('anamnesis_quiropraxia', [
            'anamnesis_id' => $anamnesis->id,
            'implantes_metales' => true,
            'embarazo' => true,
            'semanas_gestacion' => 20,
            'ciatica' => true,
            'calidad_sueno' => 'moderado',
            'desplazamiento' => 'ligero',
            'diagnostico' => 'Limitación de movilidad lumbar',
        ]);
        $specialtyRecord = $anamnesis->quiropraxia;
        $this->assertDatabaseHas('auditoria', [
            'tabla_afectada' => 'anamnesis_quiropraxia',
            'registro_id' => $specialtyRecord->id,
            'accion' => 'crear',
            'user_id' => $this->clinician->id,
        ]);
    }

    public function test_acupuncture_json_fields_are_saved_as_arrays_and_reloaded_as_lines(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment('homeopatia');
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->set('form.motivo_consulta', 'Dolor cervical')
            ->set('assessmentForms.acupuntura.pulso_cun_der_superficial', 'Tenso')
            ->set('assessmentForms.acupuntura.humedad', 'humeda')
            ->set('assessmentForms.acupuntura.lengua_color', "Rojo\nPálido")
            ->set('assessmentForms.acupuntura.diagnostico_8_reglas', "Interior\nCalor")
            ->set('assessmentForms.acupuntura.puntos_sugeridos', "LI4\nGB20")
            ->call('save')
            ->assertHasNoErrors();

        $anamnesis = AnamnesisGeneral::query()->where('consulta_id', $consultation->id)->firstOrFail();
        $record = $anamnesis->acupuntura;
        $this->assertSame(['Rojo', 'Pálido'], $record->lengua_color);
        $this->assertSame(['Interior', 'Calor'], $record->diagnostico_8_reglas);
        $this->assertSame(['LI4', 'GB20'], $record->puntos_sugeridos);
        $this->assertSame('Tenso', $record->pulso_cun_der_superficial);
        $this->assertSame('humeda', $record->humedad);

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation->fresh()])
            ->assertSet('assessmentForms.acupuntura.lengua_color', implode(PHP_EOL, ['Rojo', 'Pálido']))
            ->assertSet('assessmentForms.acupuntura.puntos_sugeridos', implode(PHP_EOL, ['LI4', 'GB20']));
    }

    public function test_specialty_anamnesis_rejects_enum_values_outside_schema(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment('homeopatia');
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->set('form.motivo_consulta', 'Dolor lumbar')
            ->set('assessmentForms.quiropraxia.calidad_sueno', 'excelente')
            ->call('save')
            ->assertHasErrors('assessmentForms.quiropraxia.calidad_sueno');

        $this->assertDatabaseCount('anamnesis_generales', 0);
    }

    public function test_anamnesis_requires_a_reason_and_rejects_invalid_measurements(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->set('form.peso_kg', '0')
            ->set('form.talla_cm', '170')
            ->call('save')
            ->assertHasErrors([
                'form.motivo_consulta' => 'required',
                'form.peso_kg' => 'gt',
            ]);

        $this->assertDatabaseCount('anamnesis_generales', 0);
    }

    public function test_closed_consultation_cannot_be_edited(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $consultation->update(['estado' => 'cerrada']);
        $this->clinician->givePermissionTo('consultations.edit');

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->assertSee('Consulta no editable')
            ->assertDontSee('Guardar anamnesis');

        $this->assertDatabaseCount('anamnesis_generales', 0);
    }

    public function test_anamnesis_route_requires_its_edit_permission(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(\App\Services\ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $viewer = User::create([
            'email' => 'consultation-viewer@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);
        $viewer->givePermissionTo(['page.view.patients', 'patients.clinical.view']);

        $this->actingAs($viewer)
            ->get(route('admin.consultations.show', $consultation))
            ->assertForbidden();

        $viewer->givePermissionTo('consultations.edit');
        $this->actingAs($viewer)
            ->get(route('admin.consultations.show', $consultation))
            ->assertOk();
    }

    public function test_close_requires_final_observations_and_records_the_transition(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $closer = User::create([
            'email' => 'consultation-closer@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);
        $closer->givePermissionTo('consultations.close');

        Livewire::actingAs($closer)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->assertSee('¿Cerrar esta consulta?')
            ->assertSee('Confirmar cierre')
            ->set('form.motivo_consulta', 'Control clínico')
            ->call('closeConsultation')
            ->assertHasErrors('form.observaciones_profesional');

        $this->assertDatabaseHas('consultas', [
            'id' => $consultation->id,
            'estado' => 'abierta',
        ]);
        $this->assertDatabaseCount('anamnesis_generales', 0);

        Livewire::actingAs($closer)
            ->test(ConsultationForm::class, ['consultation' => $consultation->fresh()])
            ->set('form.motivo_consulta', 'Control clínico')
            ->set('form.observaciones_profesional', 'Indicaciones y seguimiento registrados')
            ->call('closeConsultation')
            ->assertHasNoErrors()
            ->assertSet('consultation.estado', 'cerrada');

        $this->assertDatabaseHas('consultas', [
            'id' => $consultation->id,
            'estado' => 'cerrada',
        ]);
        $this->assertDatabaseHas('anamnesis_generales', [
            'consulta_id' => $consultation->id,
            'motivo_consulta' => 'Control clínico',
            'observaciones_profesional' => 'Indicaciones y seguimiento registrados',
        ]);

        $transition = Auditoria::query()
            ->where('tabla_afectada', 'consultas')
            ->where('registro_id', $consultation->id)
            ->where('accion', 'editar')
            ->firstOrFail();
        $this->assertSame(['estado' => 'abierta'], $transition->datos_anteriores);
        $this->assertSame([
            'estado' => 'cerrada',
            'transicion' => 'cerrar',
        ], $transition->datos_nuevos);
    }

    public function test_reopen_permission_is_independent_and_returns_consultation_to_in_progress(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $consultation->update(['estado' => 'cerrada']);
        $reopener = User::create([
            'email' => 'consultation-reopener@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);
        $reopener->givePermissionTo('consultations.reopen');

        $this->actingAs($reopener)
            ->get(route('admin.consultations.show', $consultation))
            ->assertOk();

        Livewire::actingAs($reopener)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->assertSee('Reabrir consulta')
            ->assertSee('¿Reabrir esta consulta?')
            ->assertSee('Confirmar reapertura')
            ->call('reopenConsultation')
            ->assertHasNoErrors()
            ->assertSet('consultation.estado', 'en_proceso');

        $this->assertDatabaseHas('consultas', [
            'id' => $consultation->id,
            'estado' => 'en_proceso',
        ]);
        $this->assertDatabaseHas('auditoria', [
            'tabla_afectada' => 'consultas',
            'registro_id' => $consultation->id,
            'accion' => 'editar',
            'user_id' => $reopener->id,
        ]);
    }

    public function test_canceled_consultation_cannot_be_closed_or_reopened(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $consultation->update(['estado' => 'cancelada']);
        $this->clinician->givePermissionTo([
            'consultations.close',
            'consultations.reopen',
        ]);

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->set('form.motivo_consulta', 'Control clínico')
            ->set('form.observaciones_profesional', 'Nota final')
            ->call('closeConsultation')
            ->assertHasErrors('consultation')
            ->call('reopenConsultation')
            ->assertHasErrors('consultation');

        $this->assertDatabaseHas('consultas', [
            'id' => $consultation->id,
            'estado' => 'cancelada',
        ]);
    }

    private function createConfirmedAppointment(string $specialtySlug = 'homeopatia'): array
    {
        $specialty = Especialidad::query()->where('slug', $specialtySlug)->firstOrFail();
        $specialist = Profesional::query()
            ->whereHas('especialidades', fn ($query) => $query->whereKey($specialty->id))
            ->firstOrFail();
        $branch = $specialist->sucursales()->firstOrFail();
        $patient = Paciente::create([
            'nombres' => 'Paciente clínico',
            'apellidos' => 'Prueba',
            'telefono' => '5555-0101',
        ]);
        $patient->expediente()->create();
        $appointment = $this->createAppointment(
            $patient,
            $branch,
            $specialist,
            $specialty,
            'confirmada',
        );

        return [$patient, $appointment, $specialist, $specialty, $branch];
    }

    private function createAppointment(
        Paciente $patient,
        Sucursal $branch,
        Profesional $specialist,
        Especialidad $specialty,
        string $status,
    ): Cita {
        return $patient->expediente->citas()->create([
            'sucursal_id' => $branch->id,
            'profesional_id' => $specialist->id,
            'especialidad_id' => $specialty->id,
            'tipo_cita' => 'seguimiento',
            'estado' => $status,
            'fecha_hora_inicio' => now()->addHour(),
            'fecha_hora_fin' => now()->addHours(2),
        ]);
    }
}
