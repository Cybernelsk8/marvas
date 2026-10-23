<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Schedule;
use App\Models\Admin\Setting;
use App\Models\Admin\User;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Sucursal;
use App\Services\AppointmentManagementService;
use App\Services\ClinicAvailabilityService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Seeders\CatalogosClinicosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        app(CatalogosClinicosSeeder::class)->run();

        $this->receptionist = User::create([
            'email' => 'receptionist-schedule@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);
        $this->receptionist->assignRole('Recepcionista');
    }

    public function test_reception_can_view_appointments_by_selected_date_and_filter_status(): void
    {
        $date = now()->addDays(2)->toDateString();
        $confirmed = $this->createAppointment('confirmada', 'Paciente confirmada', $date.' 09:00');
        $cancelled = $this->createAppointment('cancelada', 'Paciente cancelada', $date.' 11:00');
        $otherDay = $this->createAppointment('confirmada', 'Paciente otro día', now()->addDays(3)->toDateString().' 09:00');

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->assertSet('selectedDate', today()->toDateString())
            ->dispatch('calendar-day-selected', date: $date, events: [])
            ->assertSet('selectedDate', $date)
            ->assertSee('Paciente confirmada')
            ->assertDontSee('Paciente cancelada')
            ->assertDontSee('Paciente otro día')
            ->set('status', 'cancelada')
            ->assertSee('Paciente cancelada')
            ->assertDontSee('Paciente confirmada');
    }

    public function test_schedule_selection_rejects_invalid_dates(): void
    {
        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->call('selectDate', 'not-a-date')
            ->assertHasErrors('selectedDate')
            ->assertSet('selectedDate', today()->toDateString());
    }

    public function test_week_view_shows_appointments_for_all_days_of_the_selected_week(): void
    {
        $monday = now()->startOfWeek()->addWeek();
        $tuesday = $monday->copy()->addDay();
        $this->createAppointment('confirmada', 'Paciente lunes', $monday->toDateString().' 09:00');
        $this->createAppointment('confirmada', 'Paciente martes', $tuesday->toDateString().' 09:00');

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->call('setViewMode', 'week')
            ->call('selectDate', $monday->toDateString())
            ->assertSee('Paciente lunes')
            ->assertSee('Paciente martes');
    }

    public function test_schedule_filters_appointments_by_branch_or_unassigned_branch(): void
    {
        $date = now()->addDays(2)->toDateString();
        $branchA = Sucursal::query()->firstOrFail();
        $branchB = Sucursal::create([
            'nombre' => 'Sede de prueba secundaria',
            'activa' => true,
        ]);
        $this->createAppointment('confirmada', 'Paciente sede A', $date.' 09:00', $branchA->id);
        $this->createAppointment('confirmada', 'Paciente sede B', $date.' 10:00', $branchB->id);
        $this->createAppointment('solicitada', 'Paciente sin sede', $date.' 11:00');

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->call('selectDate', $date)
            ->assertSee('Paciente sede A')
            ->assertSee('Paciente sede B')
            ->assertSee('Paciente sin sede')
            ->set('sucursalFiltro', (string) $branchA->id)
            ->assertSee('Paciente sede A')
            ->assertDontSee('Paciente sede B')
            ->assertDontSee('Paciente sin sede')
            ->set('sucursalFiltro', 'sin_sede')
            ->assertSee('Paciente sin sede')
            ->assertDontSee('Paciente sede A')
            ->assertDontSee('Paciente sede B');
    }

    public function test_schedule_branch_filter_rejects_an_unknown_branch(): void
    {
        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->set('sucursalFiltro', '999999')
            ->assertHasErrors('sucursalFiltro');
    }

    public function test_schedule_filters_appointments_by_professional_or_unassigned_professional(): void
    {
        $date = now()->addDays(2)->toDateString();
        $professionals = Profesional::query()->orderBy('id')->take(2)->get();
        $this->assertCount(2, $professionals);

        $this->createAppointment('confirmada', 'Paciente profesional A', $date.' 09:00', null, $professionals[0]->id);
        $this->createAppointment('confirmada', 'Paciente profesional B', $date.' 10:00', null, $professionals[1]->id);
        $this->createAppointment('solicitada', 'Paciente sin profesional', $date.' 11:00');

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->call('selectDate', $date)
            ->set('profesionalFiltro', (string) $professionals[0]->id)
            ->assertSee('Paciente profesional A')
            ->assertDontSee('Paciente profesional B')
            ->assertDontSee('Paciente sin profesional')
            ->set('profesionalFiltro', 'sin_asignar')
            ->assertSee('Paciente sin profesional')
            ->assertDontSee('Paciente profesional A')
            ->assertDontSee('Paciente profesional B');
    }

    public function test_schedule_professional_filter_rejects_an_unknown_professional(): void
    {
        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->set('profesionalFiltro', '999999')
            ->assertHasErrors('profesionalFiltro');
    }

    public function test_reception_can_confirm_an_appointment_from_the_schedule(): void
    {
        $date = $this->nextWeekday()->toDateString();
        $cita = $this->createAppointment('solicitada', 'Paciente para gestionar', $date.' 09:00');
        $sucursal = Sucursal::query()->firstOrFail();
        $profesional = $sucursal->evaluadorMbi()->firstOrFail()->profesional;
        $especialidad = Especialidad::query()->where('slug', 'evaluacion-mbi')->firstOrFail();

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->call('selectDate', $date)
            ->assertSee('Confirmar')
            ->assertSee('Reprogramar')
            ->assertSee('Cancelar')
            ->call('prepareConfirmation', $cita->id)
            ->assertSee('Evaluador MBI')
            ->set('sucursalConfirmacion', (string) $sucursal->id)
            ->set('profesionalConfirmacion', (string) $profesional->id)
            ->set('especialidadConfirmacion', (string) $especialidad->id)
            ->call('confirmAppointment');

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'confirmada',
            'sucursal_id' => $sucursal->id,
            'profesional_id' => $profesional->id,
            'especialidad_id' => $especialidad->id,
            'agendada_por' => $this->receptionist->id,
        ]);
    }

    public function test_confirmation_rejects_a_specialty_not_associated_with_the_selected_professional(): void
    {
        $date = now()->addDays(2)->toDateString();
        $appointment = $this->createAppointment('solicitada', 'Paciente especialidad incorrecta', $date.' 09:00');
        $branch = Sucursal::query()->firstOrFail();
        $professional = $branch->profesionales()->firstOrFail();
        $unrelatedSpecialty = Especialidad::query()
            ->whereDoesntHave('profesionales', fn ($query) => $query->whereKey($professional->id))
            ->firstOrFail();

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->call('prepareConfirmation', $appointment->id)
            ->set('sucursalConfirmacion', (string) $branch->id)
            ->set('profesionalConfirmacion', (string) $professional->id)
            ->set('especialidadConfirmacion', (string) $unrelatedSpecialty->id)
            ->call('confirmAppointment')
            ->assertHasErrors('especialidadConfirmacion');

        $this->assertDatabaseHas('citas', [
            'id' => $appointment->id,
            'estado' => 'solicitada',
            'especialidad_id' => null,
        ]);
    }

    public function test_first_visit_confirmation_rejects_a_professional_not_assigned_as_branch_mbi_evaluator(): void
    {
        $date = $this->nextWeekday()->toDateString();
        $appointment = $this->createAppointment('solicitada', 'Paciente con evaluador incorrecto', $date.' 09:00');
        $branch = Sucursal::query()->where('activa', true)->firstOrFail();
        $professional = $branch->profesionales()
            ->whereDoesntHave('especialidades', fn ($query) => $query->where('slug', 'evaluacion-mbi'))
            ->firstOrFail();
        $specialty = $professional->especialidades()->firstOrFail();

        $result = app(AppointmentManagementService::class)->confirm(
            $appointment->id,
            $branch->id,
            $this->receptionist->id,
            $professional->id,
            $specialty->id,
        );

        $this->assertSame('evaluador_no_disponible', $result);
        $this->assertDatabaseHas('citas', [
            'id' => $appointment->id,
            'estado' => 'solicitada',
            'profesional_id' => null,
        ]);
    }

    public function test_confirmed_appointment_with_missing_assignment_can_be_completed_from_the_schedule(): void
    {
        $date = now()->addDays(2)->toDateString();
        $appointment = $this->createAppointment('confirmada', 'Paciente con asignación pendiente', $date.' 09:00');
        $branch = Sucursal::query()->where('activa', true)->firstOrFail();
        $professional = $branch->evaluadorMbi()->firstOrFail()->profesional;
        $specialty = Especialidad::query()->where('slug', 'evaluacion-mbi')->firstOrFail();

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->call('selectDate', $date)
            ->assertSee('Completar asignación')
            ->call('prepareConfirmation', $appointment->id)
            ->assertSet('assigningConfirmedAppointment', true)
            ->set('sucursalConfirmacion', (string) $branch->id)
            ->set('profesionalConfirmacion', (string) $professional->id)
            ->set('especialidadConfirmacion', (string) $specialty->id)
            ->call('confirmAppointment')
            ->assertSet('assigningConfirmedAppointment', false);

        $this->assertDatabaseHas('citas', [
            'id' => $appointment->id,
            'estado' => 'confirmada',
            'sucursal_id' => $branch->id,
            'profesional_id' => $professional->id,
            'especialidad_id' => $specialty->id,
        ]);
        $this->assertDatabaseHas('auditoria', [
            'tabla_afectada' => 'citas',
            'registro_id' => $appointment->id,
            'accion' => 'editar',
            'user_id' => $this->receptionist->id,
        ]);
    }

    public function test_single_available_professional_and_specialty_are_selected_automatically(): void
    {
        $branch = Sucursal::create([
            'nombre' => 'Sede con profesional único',
            'activa' => true,
        ]);
        $professional = Profesional::create([
            'user_id' => $this->receptionist->id,
            'nombre_completo' => 'Profesional único de prueba',
            'activo' => true,
        ]);
        $specialty = Especialidad::query()->where('slug', 'homeopatia')->firstOrFail();
        $branch->profesionales()->attach($professional->id);
        $professional->especialidades()->attach($specialty->id);

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->set('sucursalConfirmacion', (string) $branch->id)
            ->assertSet('profesionalConfirmacion', (string) $professional->id)
            ->assertSet('especialidadConfirmacion', (string) $specialty->id);
    }

    public function test_reception_can_reschedule_an_appointment_from_the_schedule(): void
    {
        $cita = $this->createAppointment(
            'solicitada',
            'Paciente para reprogramar',
            now()->addDays(2)->toDateString().' 09:00',
        );
        $newDate = $this->nextWeekday()->addWeek()->toDateString();

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->call('prepareRescheduling', $cita->id)
            ->set('reprogramacion.fecha', $newDate)
            ->set('reprogramacion.hora', '10:00')
            ->call('rescheduleAppointment');

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'solicitada',
            'fecha_hora_inicio' => $newDate.' 10:00:00',
        ]);
    }

    public function test_rescheduling_lists_the_appointment_own_slot_as_available(): void
    {
        $date = $this->nextWeekday()->addWeek()->toDateString();
        $appointment = $this->createAppointment(
            'solicitada',
            'Paciente horario propio',
            $date.' 09:00',
        );

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->call('prepareRescheduling', $appointment->id)
            ->assertSee('09:00 am');
    }

    public function test_rescheduling_uses_the_configured_appointment_duration(): void
    {
        $cita = $this->createAppointment(
            'solicitada',
            'Paciente con duración configurable',
            now()->addDays(2)->toDateString().' 09:00',
        );
        $newDate = $this->nextWeekday()->addWeek()->toDateString();
        Setting::query()->updateOrCreate(
            ['key' => ClinicAvailabilityService::SETTING_KEY],
            [
                'type' => 'array',
                'value' => [
                    'groups' => [[
                        'days' => [1, 2, 3, 4, 5],
                        'hours' => ['08:00', '10:00', '13:00', '15:00'],
                    ]],
                    'duration_minutes' => 120,
                    'closures' => [],
                ],
            ],
        );

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->call('prepareRescheduling', $cita->id)
            ->set('reprogramacion.fecha', $newDate)
            ->set('reprogramacion.hora', '10:00')
            ->call('rescheduleAppointment');

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'solicitada',
            'fecha_hora_inicio' => $newDate.' 10:00:00',
            'fecha_hora_fin' => $newDate.' 12:00:00',
            'duracion_minutos' => 120,
        ]);
    }

    public function test_reception_can_cancel_an_appointment_from_the_schedule(): void
    {
        $cita = $this->createAppointment(
            'solicitada',
            'Paciente para cancelar',
            now()->addDays(2)->toDateString().' 09:00',
        );

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->call('prepareCancellation', $cita->id)
            ->set('motivoCancelacion', 'El paciente solicitó cancelar.')
            ->call('cancelAppointment');

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'cancelada',
            'motivo_cancelacion' => 'El paciente solicitó cancelar.',
        ]);
    }

    public function test_users_without_schedule_permission_cannot_access_the_calendar(): void
    {
        $user = User::create([
            'email' => 'no-schedule-access@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/admin/schedule')
            ->assertForbidden();

        $this->actingAs($this->receptionist)
            ->get('/admin/schedule')
            ->assertOk();
    }

    private function createAppointment(
        string $status,
        string $name,
        string $start,
        ?int $sucursalId = null,
        ?int $profesionalId = null,
    ): Cita {
        $paciente = Paciente::create([
            'nombres' => $name,
            'apellidos' => 'Prueba',
            'telefono' => '5555-1234',
        ]);
        $expediente = $paciente->expediente()->create();
        $inicio = Carbon::parse($start);

        return $expediente->citas()->create([
            'sucursal_id' => $sucursalId,
            'profesional_id' => $profesionalId,
            'tipo_cita' => 'primera_vez',
            'estado' => $status,
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addHour(),
        ]);
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
