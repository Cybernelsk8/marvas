<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Consultation\PainMap;
use App\Livewire\Admin\Consultation\Show as ConsultationForm;
use App\Livewire\Admin\Patient\Show;
use App\Models\Admin\User;
use App\Models\AnamnesisGeneral;
use App\Models\Auditoria;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Especialidad;
use App\Models\EvaluacionIntegralMbi;
use App\Models\MapaCorporal;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\RegionCorporal;
use App\Models\Sucursal;
use App\Models\ZonaDolor;
use App\Services\ConsultationService;
use Database\Seeders\CatalogosClinicosSeeder;
use Database\Seeders\RegionesCorporalesSeeder;
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

    public function test_pain_zone_history_is_comparable_per_region_across_consultations(): void
    {
        $this->seed(RegionesCorporalesSeeder::class);
        $region = RegionCorporal::where('codigo', 'lumbar')->firstOrFail();
        [$patient, $first, $specialist, $specialty, $branch] = $this->createConfirmedAppointment();
        $second = $this->createAppointment($patient, $branch, $specialist, $specialty, 'confirmada');

        Livewire::actingAs($this->clinician)
            ->test(Show::class, ['patient' => $patient])
            ->call('startConsultation', $first->id)
            ->call('startConsultation', $second->id);

        $niveles = [];
        foreach (Consulta::query()->orderBy('numero_consulta')->get() as $i => $consulta) {
            $eva = [8, 5][$i];
            $mapa = MapaCorporal::create(['consulta_id' => $consulta->id, 'estado' => 'con_dolor', 'eva_maximo' => $eva]);
            $niveles[] = ZonaDolor::create([
                'mapa_id' => $mapa->id,
                'region_id' => $region->id,
                'lateralidad' => 'central',
                'eva' => $eva,
                'tipos' => ['punzante'],
            ])->nivel;
        }

        $this->assertSame(['intenso', 'moderado'], $niveles);
        $this->assertSame(2, $region->zonasDolor()->count());
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

    public function test_general_and_specialty_anamneses_are_saved_independently_without_writing_generated_bmi(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');

        $component = Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->assertSee('Formularios de la consulta')
            ->assertSee('Anamnesis general')
            ->assertSee('Evaluación homeopática')
            ->assertSee('Evaluación quiropráctica')
            ->assertSee('Evaluación de acupuntura')
            ->call('openAnamnesis', 'general')
            ->set('form.motivo_consulta', 'Dolor de prueba')
            ->set('form.historia_padecimiento', 'Comenzó hace dos días')
            ->set('form.antecedentes_estados.alergias.estado', 'si')
            ->set('form.antecedentes_estados.alergias.detalle', 'Penicilina')
            ->set('form.revision_sistemas.respiratorio.estado', 'con_sintomas')
            ->set('form.revision_sistemas.respiratorio.detalle', 'Tos nocturna')
            ->set('form.intensidad_dolor', '6')
            ->set('form.presion_sistolica', '120')
            ->set('form.presion_diastolica', '80')
            ->set('form.frecuencia_cardiaca', '72')
            ->set('form.temperatura', '36.5')
            ->set('form.peso_kg', '70')
            ->set('form.talla_cm', '170')
            ->call('saveGeneralAnamnesis')
            ->assertHasNoErrors()
            ->assertSee('Guardado');

        $anamnesis = AnamnesisGeneral::query()->where('consulta_id', $consultation->id)->firstOrFail();
        $this->assertSame('Dolor de prueba', $anamnesis->motivo_consulta);
        $this->assertSame('si', $anamnesis->antecedentes_estados['alergias']['estado']);
        $this->assertSame('Penicilina', $anamnesis->antecedentes_estados['alergias']['detalle']);
        $this->assertSame('con_sintomas', $anamnesis->revision_sistemas['respiratorio']['estado']);
        $this->assertSame(120, $anamnesis->presion_sistolica);
        $this->assertEquals(24.22, (float) $anamnesis->imc);
        $this->assertDatabaseCount('anamnesis_homeopatica', 0);
        $this->assertDatabaseCount('anamnesis_quiropraxia', 0);
        $this->assertDatabaseCount('anamnesis_acupuntura', 0);

        $component
            ->call('openAnamnesis', 'homeopatia')
            ->set('assessmentForms.homeopatia.aspecto_paciente', 'Aspecto sereno')
            ->set('assessmentForms.homeopatia.sintomas_mentales', 'Sueño inquieto')
            ->call('saveAssessment', 'homeopatia')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('anamnesis_homeopatica', [
            'anamnesis_id' => $anamnesis->id,
            'aspecto_paciente' => 'Aspecto sereno',
            'sintomas_mentales' => 'Sueño inquieto',
        ]);
        $this->assertDatabaseCount('anamnesis_quiropraxia', 0);
        $this->assertDatabaseCount('anamnesis_acupuntura', 0);

        $component
            ->call('openAnamnesis', 'quiropraxia')
            ->set('assessmentForms.quiropraxia.diagnostico', 'Sin hallazgos musculoesqueléticos')
            ->call('saveAssessment', 'quiropraxia')
            ->assertHasNoErrors()
            ->call('openAnamnesis', 'acupuntura')
            ->set('assessmentForms.acupuntura.diagnostico_energetico', 'Qi equilibrado')
            ->call('saveAssessment', 'acupuntura')
            ->assertHasNoErrors()
            ->assertSee('Formularios de la consulta');
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
            ->assertSet('form.antecedentes_estados.alergias.detalle', 'Penicilina')
            ->assertSet('assessmentForms.homeopatia.aspecto_paciente', 'Aspecto sereno')
            ->assertSet('assessmentForms.quiropraxia.diagnostico', 'Sin hallazgos musculoesqueléticos')
            ->assertSet('assessmentForms.acupuntura.diagnostico_energetico', 'Qi equilibrado')
            ->call('openAnamnesis', 'general')
            ->set('form.motivo_consulta', 'Dolor actualizado')
            ->call('saveGeneralAnamnesis')
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

    public function test_integral_anamnesis_saves_sections_together_and_shows_evaluator_result_on_patient_file(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->call('openAnamnesis', 'general')
            ->set('form.motivo_consulta', 'Dolor lumbar')
            ->set('form.datos_formulario.consulta_actual.inicio', 'Hace dos semanas')
            ->set('form.datos_formulario.antecedentes_habitos.tabaco', 'No fuma')
            ->set('form.datos_formulario.examen_general.examenes_complementarios', 'Sin hallazgos')
            ->set('assessmentForms.homeopatia.sintomas_mentales', 'Sueño inquieto')
            ->set('assessmentForms.homeopatia.datos_complementarios.sensibilidad_termica', 'Sensible al frío')
            ->set('assessmentForms.quiropraxia.diagnostico', 'Dolor lumbar mecánico')
            ->set('assessmentForms.quiropraxia.seguridad.deficit_neurologico.estado', 'no')
            ->set('form.datos_formulario.resultado_evaluador.resumen_clinico', 'Síntomas y hallazgos documentados')
            ->set('form.datos_formulario.resultado_evaluador.diagnostico_diferenciales', 'Hipótesis clínica')
            ->set('form.datos_formulario.resultado_evaluador.plan_seguimiento', 'Reevaluar en seguimiento')
            ->call('saveIntegralAnamnesis')
            ->assertHasNoErrors()
            ->assertSee('Formularios de la consulta');

        $anamnesis = AnamnesisGeneral::query()->where('consulta_id', $consultation->id)->firstOrFail();
        $this->assertSame('Hace dos semanas', $anamnesis->datos_formulario['consulta_actual']['inicio']);
        $this->assertSame('No fuma', $anamnesis->datos_formulario['antecedentes_habitos']['tabaco']);
        $this->assertSame('Sin hallazgos', $anamnesis->datos_formulario['examen_general']['examenes_complementarios']);
        $this->assertSame(
            'Sensible al frío',
            $anamnesis->datos_formulario['homeopatia']['sensibilidad_termica'],
        );
        $this->assertSame('no', $anamnesis->quiropraxia->seguridad['deficit_neurologico']['estado']);
        $this->assertDatabaseHas('anamnesis_homeopatica', [
            'anamnesis_id' => $anamnesis->id,
            'sintomas_mentales' => 'Sueño inquieto',
        ]);
        $this->assertDatabaseHas('anamnesis_quiropraxia', [
            'anamnesis_id' => $anamnesis->id,
            'diagnostico' => 'Dolor lumbar mecánico',
        ]);

        Livewire::actingAs($this->clinician)
            ->test(Show::class, ['patient' => $patient])
            ->assertSee('07 · Resultado del evaluador')
            ->assertSee('Síntomas y hallazgos documentados')
            ->assertSee('Hipótesis clínica')
            ->assertSee('Reevaluar en seguimiento');
    }

    public function test_mbi_evaluation_saves_lists_and_fields_with_audited_changes(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');

        $component = Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->assertSee('Evaluación integral MBI')
            ->call('openAnamnesis', 'mbi')
            ->assertSee('Sistemas comprometidos (uno por línea)')
            ->set('mbiForm.integracion_clinica', 'Integración de hallazgos clínicos')
            ->set('mbiForm.sistemas_comprometidos', "Digestivo\nNeurológico")
            ->set('mbiForm.hallazgos_principales', 'Hallazgos documentados')
            ->set('mbiForm.objetivo_terapeutico', 'Objetivo de evaluación')
            ->set('mbiForm.especialidad_principal', 'Naturopatía')
            ->set('mbiForm.especialidades_complementarias', "Homeopatía\nQuiropraxia")
            ->set('mbiForm.modelo_6plus_seleccion', "Área 1\nÁrea 2")
            ->set('mbiForm.observaciones_generales', 'Observación administrativa de prueba')
            ->call('saveMbiEvaluation')
            ->assertHasNoErrors()
            ->assertSee('Guardado');

        $evaluation = EvaluacionIntegralMbi::query()
            ->where('consulta_id', $consultation->id)
            ->firstOrFail();
        $this->assertSame('Integración de hallazgos clínicos', $evaluation->integracion_clinica);
        $this->assertSame(['Digestivo', 'Neurológico'], $evaluation->sistemas_comprometidos);
        $this->assertSame(['Homeopatía', 'Quiropraxia'], $evaluation->especialidades_complementarias);
        $this->assertSame(['Área 1', 'Área 2'], $evaluation->modelo_6plus_seleccion);

        $audit = Auditoria::query()
            ->where('tabla_afectada', 'evaluacion_integral_mbi')
            ->where('registro_id', $evaluation->id)
            ->firstOrFail();
        $this->assertSame('crear', $audit->accion);
        $this->assertContains('integracion_clinica', $audit->datos_nuevos['campos_modificados']);
        $this->assertStringNotContainsString('Integración de hallazgos', json_encode($audit->toArray()));

        $component
            ->call('openAnamnesis', 'mbi')
            ->assertSet('mbiForm.sistemas_comprometidos', "Digestivo\nNeurológico")
            ->set('mbiForm.integracion_clinica', 'Integración actualizada')
            ->call('saveMbiEvaluation')
            ->assertHasNoErrors();

        $this->assertSame(
            1,
            EvaluacionIntegralMbi::query()->where('consulta_id', $consultation->id)->count(),
        );
        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation->fresh()])
            ->call('openAnamnesis', 'mbi')
            ->assertSet('mbiForm.integracion_clinica', 'Integración actualizada')
            ->assertSet('mbiForm.especialidades_complementarias', implode(PHP_EOL, ['Homeopatía', 'Quiropraxia']));

        Livewire::actingAs($this->clinician)
            ->test(Show::class, ['patient' => $patient])
            ->assertSee('Evaluación integral MBI')
            ->assertSee('Integración actualizada')
            ->assertSee('Digestivo, Neurológico');

        $this->assertDatabaseHas('evaluacion_integral_mbi', [
            'consulta_id' => $consultation->id,
            'integracion_clinica' => 'Integración actualizada',
        ]);
        $this->assertDatabaseHas('auditoria', [
            'tabla_afectada' => 'evaluacion_integral_mbi',
            'registro_id' => $evaluation->id,
            'accion' => 'editar',
            'user_id' => $this->clinician->id,
        ]);
    }

    public function test_mbi_evaluation_cannot_be_saved_for_a_closed_consultation(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $consultation->update(['estado' => 'cerrada']);
        $this->clinician->givePermissionTo('consultations.edit');

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->set('mbiForm.integracion_clinica', 'No debe persistir')
            ->call('saveMbiEvaluation')
            ->assertHasErrors('mbi');

        $this->assertDatabaseCount('evaluacion_integral_mbi', 0);
        $this->assertDatabaseCount('auditoria', 1);
    }

    public function test_pain_map_saves_zones_derives_max_eva_and_audits(): void
    {
        $this->seed(RegionesCorporalesSeeder::class);
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');
        $lumbar = RegionCorporal::where('codigo', 'lumbar')->firstOrFail();
        $biceps = RegionCorporal::where('codigo', 'biceps')->firstOrFail();

        Livewire::actingAs($this->clinician)
            ->test(PainMap::class, ['consultationId' => $consultation->id, 'editable' => true])
            ->call('select', $lumbar->id, 'central')
            ->set('zone.eva', 8)
            ->set('zone.tipos', ['punzante', 'rigidez'])
            ->set('zone.irradiacion', ['gluteos'])
            ->call('applyZone')
            ->assertHasNoErrors()
            ->call('select', $biceps->id, 'derecha')
            ->set('zone.eva', 3)
            ->call('applyZone')
            ->call('save')
            ->assertDispatched('pain-map-saved', eva: 8, zona: 'Lumbar');

        $mapa = MapaCorporal::where('consulta_id', $consultation->id)->firstOrFail();
        $this->assertSame('con_dolor', $mapa->estado);
        $this->assertSame(8, $mapa->eva_maximo);
        $this->assertSame(2, $mapa->zonasDolor()->count());
        $this->assertSame('intenso', $mapa->zonasDolor()->where('region_id', $lumbar->id)->first()->nivel);
        $this->assertDatabaseHas('auditoria', ['tabla_afectada' => 'mapas_corporales', 'accion' => 'crear']);

        Livewire::actingAs($this->clinician)
            ->test(PainMap::class, ['consultationId' => $consultation->id, 'editable' => true])
            ->assertSet('zones.'.$lumbar->id.'-central.eva', 8)
            ->call('removeZone', $biceps->id.'-derecha')
            ->call('save');
        $this->assertSame(1, $mapa->zonasDolor()->count());
    }

    public function test_pain_map_rejects_invalid_eva_and_lateral_side(): void
    {
        $this->seed(RegionesCorporalesSeeder::class);
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');
        $lumbar = RegionCorporal::where('codigo', 'lumbar')->firstOrFail();

        Livewire::actingAs($this->clinician)
            ->test(PainMap::class, ['consultationId' => $consultation->id, 'editable' => true])
            ->call('select', $lumbar->id, 'central')
            ->set('zone.eva', 11)
            ->call('applyZone')
            ->assertHasErrors('zone.eva');

        Livewire::actingAs($this->clinician)
            ->test(PainMap::class, ['consultationId' => $consultation->id, 'editable' => true])
            ->call('select', $lumbar->id, 'derecha')
            ->assertStatus(422);
    }

    public function test_pain_map_shows_history_and_comparison_with_previous_sessions(): void
    {
        $this->seed(RegionesCorporalesSeeder::class);
        [$patient, $first, $specialist, $specialty, $branch] = $this->createConfirmedAppointment();
        $second = $this->createAppointment($patient, $branch, $specialist, $specialty, 'confirmada');
        $service = app(ConsultationService::class);
        $previous = $service->openFromAppointment($first->id, $patient->id);
        $current = $service->openFromAppointment($second->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');
        $lumbar = RegionCorporal::where('codigo', 'lumbar')->firstOrFail();

        $service->savePainMap($previous->id, false, null, [[
            'region_id' => $lumbar->id, 'lateralidad' => 'central', 'eva' => 8,
            'tipos' => [], 'irradiacion' => [], 'inicio' => '', 'patron' => [], 'comentario' => '',
        ]]);

        $component = Livewire::actingAs($this->clinician)
            ->test(PainMap::class, ['consultationId' => $current->id, 'editable' => true])
            ->call('select', $lumbar->id, 'central')
            ->set('zone.eva', 5)
            ->call('applyZone')
            ->call('select', $lumbar->id, 'central')
            ->set('compareId', $previous->id);

        $this->assertSame([8, 5], array_column($component->instance()->history(), 'eva'));
        $this->assertSame([$lumbar->id.'-central' => 8], $component->instance()->compareZones());
    }

    public function test_pain_map_cannot_be_saved_for_a_closed_consultation(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)->openFromAppointment($appointment->id, $patient->id);
        $consultation->update(['estado' => 'cerrada']);
        $this->clinician->givePermissionTo('consultations.edit');

        Livewire::actingAs($this->clinician)
            ->test(PainMap::class, ['consultationId' => $consultation->id, 'editable' => true])
            ->set('noPain', true)
            ->call('save')
            ->assertHasErrors('mapa');

        $this->assertDatabaseCount('mapas_corporales', 0);
    }

    public function test_consultation_form_opens_pain_map_section(): void
    {
        $this->seed(RegionesCorporalesSeeder::class);
        [$patient, $appointment] = $this->createConfirmedAppointment();
        $consultation = app(ConsultationService::class)->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->assertSee('Mapa de dolor')
            ->call('openAnamnesis', 'mapa_dolor')
            ->assertSee('Guardar mapa de dolor')
            ->dispatch('pain-map-saved', eva: 7, zona: 'Lumbar')
            ->assertSet('form.intensidad_dolor', '7')
            ->assertSet('form.zona_dolor_principal', 'Lumbar');
    }

    public function test_chiropractic_anamnesis_persists_structured_blocks_and_safety_rule(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment('homeopatia');
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');

        $component = Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->call('openAnamnesis', 'quiropraxia')
            ->set('assessmentForms.quiropraxia.sintomas.cervicalgia.estado', 'con_sintomas')
            ->set('assessmentForms.quiropraxia.pruebas_neuro_ortopedicas.lasegue.estado', 'alterado')
            ->set('assessmentForms.quiropraxia.pruebas_neuro_ortopedicas.lasegue.lado', 'derecha')
            ->set('assessmentForms.quiropraxia.pruebas_neuro_ortopedicas.fuerza_muscular.fuerza', '4')
            ->set('assessmentForms.quiropraxia.indice_funcional.sueno.estado', 'moderado')
            ->set('assessmentForms.quiropraxia.seguridad.trauma_fractura.estado', 'si')
            ->set('assessmentForms.quiropraxia.decision_manipulacion', 'indicada')
            ->set('assessmentForms.quiropraxia.diagnostico', 'Limitación de movilidad lumbar')
            ->call('saveAssessment', 'quiropraxia')
            ->assertHasErrors('assessmentForms.quiropraxia.decision_manipulacion')
            ->assertSeeHtml('data-testid="safety-alert"');

        $this->assertDatabaseCount('anamnesis_quiropraxia', 0);

        $component
            ->set('assessmentForms.quiropraxia.decision_manipulacion', 'diferida')
            ->call('saveAssessment', 'quiropraxia')
            ->assertHasNoErrors();

        $anamnesis = AnamnesisGeneral::query()->where('consulta_id', $consultation->id)->firstOrFail();
        $this->assertNull($anamnesis->motivo_consulta);
        $record = $anamnesis->quiropraxia;
        $this->assertSame('con_sintomas', $record->sintomas['cervicalgia']['estado']);
        $this->assertSame('derecha', $record->pruebas_neuro_ortopedicas['lasegue']['lado']);
        $this->assertSame('4', (string) $record->pruebas_neuro_ortopedicas['fuerza_muscular']['fuerza']);
        $this->assertSame('moderado', $record->indice_funcional['sueno']['estado']);
        $this->assertSame('diferida', $record->decision_manipulacion);
        $this->assertContains('trauma_fractura', array_keys(array_filter(
            $record->seguridad,
            fn (array $item): bool => $item['estado'] === 'si',
        )));
        $this->assertDatabaseHas('auditoria', [
            'tabla_afectada' => 'anamnesis_quiropraxia',
            'registro_id' => $record->id,
            'accion' => 'crear',
            'user_id' => $this->clinician->id,
        ]);
    }

    public function test_acupuncture_structured_blocks_are_saved_reloaded_and_validated(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment('homeopatia');
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');

        // Sin capturar nada no se crea el registro.
        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->call('openAnamnesis', 'acupuntura')
            ->call('saveAssessment', 'acupuntura')
            ->assertHasNoErrors();
        $this->assertDatabaseCount('anamnesis_acupuntura', 0);

        $component = Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->call('openAnamnesis', 'acupuntura')
            ->set('assessmentForms.acupuntura.pulsos.cun_der_superficial.calidad', 'Tenso')
            ->set('assessmentForms.acupuntura.humedad', 'humeda')
            ->set('assessmentForms.acupuntura.lengua_movimiento', 'temblorosa')
            ->set('assessmentForms.acupuntura.lengua_color', ['roja', 'purpura'])
            ->set('assessmentForms.acupuntura.diagnostico_8_reglas.temperatura', 'calor')
            ->set('assessmentForms.acupuntura.diagnostico_6_niveles', ['pie_yang_tai_yang'])
            ->set('assessmentForms.acupuntura.organos_zang_fu', ['higado'])
            ->set('assessmentForms.acupuntura.puntos_sugeridos.distal.puntos', 'IG4, H3')
            ->assertSeeHtml('data-testid="zangfu-relations"')
            ->call('saveAssessment', 'acupuntura')
            ->assertHasNoErrors();

        $anamnesis = AnamnesisGeneral::query()->where('consulta_id', $consultation->id)->firstOrFail();
        $record = $anamnesis->acupuntura;
        $this->assertNull($anamnesis->motivo_consulta);
        $this->assertSame(['roja', 'purpura'], $record->lengua_color);
        $this->assertSame('calor', $record->diagnostico_8_reglas['temperatura']);
        $this->assertNull($record->diagnostico_8_reglas['fuerza']);
        $this->assertSame('Tenso', $record->pulsos['cun_der_superficial']['calidad']);
        $this->assertSame('IG4, H3', $record->puntosPorCategoria('distal')['puntos']);
        $this->assertSame('humeda', $record->humedad);
        $this->assertSame('temblorosa', $record->lengua_movimiento);
        $this->assertNull($record->saburra_color);
        $this->assertSame(
            ['organo' => 'Hígado', 'elemento' => 'Madera', 'generacion' => 'Agua', 'control' => 'Metal'],
            $record->relacionesZangFu()[0],
        );

        $reloaded = Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation->fresh()])
            ->assertSet('assessmentForms.acupuntura.lengua_color', ['roja', 'purpura'])
            ->assertSet('assessmentForms.acupuntura.pulsos.cun_der_superficial.calidad', 'Tenso')
            ->assertSet('assessmentForms.acupuntura.pulsos.chi_izq_profundo.calidad', '')
            ->assertSet('assessmentForms.acupuntura.puntos_sugeridos.distal.puntos', 'IG4, H3');

        $component
            ->set('assessmentForms.acupuntura.lengua_forma', ['inexistente'])
            ->set('assessmentForms.acupuntura.diagnostico_8_reglas.fuerza', 'otro')
            ->call('saveAssessment', 'acupuntura')
            ->assertHasErrors([
                'assessmentForms.acupuntura.lengua_forma.0',
                'assessmentForms.acupuntura.diagnostico_8_reglas.fuerza',
            ]);
    }

    public function test_specialty_anamnesis_rejects_enum_values_outside_schema(): void
    {
        [$patient, $appointment] = $this->createConfirmedAppointment('homeopatia');
        $consultation = app(ConsultationService::class)
            ->openFromAppointment($appointment->id, $patient->id);
        $this->clinician->givePermissionTo('consultations.edit');

        Livewire::actingAs($this->clinician)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->call('openAnamnesis', 'quiropraxia')
            ->set('assessmentForms.quiropraxia.seguridad.trauma_fractura.estado', 'quizas')
            ->call('saveAssessment', 'quiropraxia')
            ->assertHasErrors('assessmentForms.quiropraxia.seguridad.trauma_fractura.estado');

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
            ->call('openAnamnesis', 'general')
            ->set('form.peso_kg', '0')
            ->set('form.talla_cm', '170')
            ->call('saveGeneralAnamnesis')
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
        $consultation = app(ConsultationService::class)
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

        $component = Livewire::actingAs($closer)
            ->test(ConsultationForm::class, ['consultation' => $consultation])
            ->assertSee('¿Cerrar esta consulta?')
            ->assertSee('Confirmar cierre')
            ->set('form.motivo_consulta', 'Borrador que no debe guardarse')
            ->set('form.historia_padecimiento', 'Borrador clínico sin guardar')
            ->set('assessmentForms.homeopatia.aspecto_paciente', 'Borrador especializado')
            ->call('closeConsultation')
            ->assertHasErrors([
                'closure.motivo_consulta',
                'closure.observaciones_profesional',
            ]);

        $this->assertDatabaseHas('consultas', [
            'id' => $consultation->id,
            'estado' => 'abierta',
        ]);
        $this->assertDatabaseCount('anamnesis_generales', 0);

        $component
            ->set('closure.motivo_consulta', 'Control clínico')
            ->set('closure.observaciones_profesional', 'Indicaciones y seguimiento registrados')
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
            'historia_padecimiento' => null,
        ]);
        $this->assertDatabaseCount('anamnesis_homeopatica', 0);

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
            ->set('closure.motivo_consulta', 'Control clínico')
            ->set('closure.observaciones_profesional', 'Nota final')
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
