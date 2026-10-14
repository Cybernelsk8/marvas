<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Schedule;
use App\Models\Admin\User;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Sucursal;
use Carbon\Carbon;
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

    public function test_reception_can_confirm_an_appointment_from_the_schedule(): void
    {
        $date = now()->addDays(2)->toDateString();
        $cita = $this->createAppointment('solicitada', 'Paciente para gestionar', $date.' 09:00');
        $sucursal = Sucursal::query()->firstOrFail();

        Livewire::actingAs($this->receptionist)
            ->test(Schedule::class)
            ->call('selectDate', $date)
            ->assertSee('Confirmar')
            ->assertSee('Reprogramar')
            ->assertSee('Cancelar')
            ->call('prepareConfirmation', $cita->id)
            ->set('sucursalConfirmacion', (string) $sucursal->id)
            ->call('confirmAppointment');

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'confirmada',
            'sucursal_id' => $sucursal->id,
            'agendada_por' => $this->receptionist->id,
        ]);
    }

    public function test_reception_can_reschedule_an_appointment_from_the_schedule(): void
    {
        $cita = $this->createAppointment(
            'solicitada',
            'Paciente para reprogramar',
            now()->addDays(2)->toDateString().' 09:00',
        );
        $newDate = now()->addDays(4)->toDateString();

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

    private function createAppointment(string $status, string $name, string $start, ?int $sucursalId = null): Cita
    {
        $paciente = Paciente::create([
            'nombres' => $name,
            'apellidos' => 'Prueba',
            'telefono' => '5555-1234',
        ]);
        $expediente = $paciente->expediente()->create();
        $inicio = Carbon::parse($start);

        return $expediente->citas()->create([
            'sucursal_id' => $sucursalId,
            'tipo_cita' => 'primera_vez',
            'estado' => $status,
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addHour(),
        ]);
    }
}
