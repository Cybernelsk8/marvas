<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Availability;
use App\Models\Admin\Setting;
use App\Models\Admin\User;
use App\Models\Cita;
use App\Models\Paciente;
use App\Services\ClinicAvailabilityService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Seeders\CatalogosClinicosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $scheduler;

    protected function setUp(): void
    {
        parent::setUp();

        app(CatalogosClinicosSeeder::class)->run();

        $this->scheduler = User::create([
            'email' => 'scheduler@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);
        $this->scheduler->givePermissionTo(['page.view.schedule', 'schedule.edit']);
    }

    public function test_authorized_user_can_configure_independent_hours_for_groups_of_days(): void
    {
        $closureStart = $this->nextWeekday()->addWeek()->toDateString();
        $closureEnd = Carbon::parse($closureStart)->addDay()->toDateString();
        $groups = [
            [
                'days' => [1, 2, 3, 4, 5],
                'hours' => ['08:00', '09:00', '14:00'],
            ],
            [
                'days' => [6],
                'hours' => ['09:30', '11:00'],
            ],
            [
                'days' => [7],
                'hours' => ['10:00'],
            ],
        ];

        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('groups', $groups)
            ->set('closures', [[
                'start_date' => $closureStart,
                'end_date' => $closureEnd,
                'reason' => 'Feriado',
            ]])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([
            'groups' => $groups,
            'duration_minutes' => 60,
            'closures' => [[
                'start_date' => $closureStart,
                'end_date' => $closureEnd,
                'reason' => 'Feriado',
            ]],
        ], Setting::query()->where('key', ClinicAvailabilityService::SETTING_KEY)->firstOrFail()->value);
    }

    public function test_duration_can_be_changed_and_hours_must_fit_without_overlapping(): void
    {
        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('durationMinutes', 120)
            ->set('groups', [
                ['days' => [1, 2, 3, 4, 5], 'hours' => ['08:00', '10:00', '13:00', '15:00']],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $configuration = app(ClinicAvailabilityService::class)->configuration();
        $this->assertSame(120, $configuration['duration_minutes']);
        $this->assertTrue(app(ClinicAvailabilityService::class)->isSlotAllowed(
            today()->next(Carbon::MONDAY)->setTime(8, 0),
            120,
            $configuration,
        ));
    }

    public function test_duration_must_be_a_multiple_of_fifteen_minutes(): void
    {
        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('durationMinutes', 50)
            ->call('save')
            ->assertHasErrors('durationMinutes');
    }

    public function test_default_schedule_has_manual_weekday_hours_and_weekends_are_closed(): void
    {
        $availability = app(ClinicAvailabilityService::class);

        $this->assertSame(
            ['08:00', '09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00'],
            $availability->recurringHours(),
        );
        $this->assertSame([], $availability->hoursForDay(6));
        $this->assertSame([], $availability->hoursForDay(7));
        $this->assertFalse($availability->isSlotAllowed(today()->next(Carbon::SATURDAY)->setTime(9, 0), 60));
    }

    public function test_weekend_hours_can_be_configured_independently_by_group(): void
    {
        $configuration = [
            'groups' => [
                ['days' => [6], 'hours' => ['09:30', '11:00']],
                ['days' => [7], 'hours' => ['10:00']],
            ],
            'duration_minutes' => 60,
            'closures' => [],
        ];
        $availability = app(ClinicAvailabilityService::class);

        $this->assertSame(['09:30', '11:00'], $availability->hoursForDay(6, $configuration));
        $this->assertSame(['10:00'], $availability->hoursForDay(7, $configuration));
        $this->assertTrue($availability->isSlotAllowed(today()->next(Carbon::SATURDAY)->setTime(9, 30), 60, $configuration));
        $this->assertFalse($availability->isSlotAllowed(today()->next(Carbon::SUNDAY)->setTime(9, 30), 60, $configuration));
        $this->assertTrue($availability->isSlotAllowed(today()->next(Carbon::SUNDAY)->setTime(10, 0), 60, $configuration));
    }

    public function test_same_hours_can_be_reused_in_groups_with_different_days(): void
    {
        $groups = [
            ['days' => [1, 2, 3, 4, 5], 'hours' => ['08:00', '09:00', '10:00', '11:00']],
            ['days' => [6], 'hours' => ['08:00', '09:00', '10:00', '11:00']],
        ];

        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('groups', $groups)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($groups, Setting::query()
            ->where('key', ClinicAvailabilityService::SETTING_KEY)
            ->firstOrFail()
            ->value['groups']);
    }

    public function test_duplicate_hours_inside_the_same_group_are_rejected(): void
    {
        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('groups', [
                ['days' => [6], 'hours' => ['08:00', '08:00']],
            ])
            ->call('save')
            ->assertHasErrors('groups.0.hours');
    }

    public function test_old_global_opening_hours_are_converted_to_a_group_without_losing_the_schedule(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => ClinicAvailabilityService::SETTING_KEY],
            [
                'type' => 'array',
                'value' => [
                    'days' => [1, 2, 3, 4, 5],
                    'opening_time' => '08:00',
                    'closing_time' => '17:00',
                    'duration_minutes' => 60,
                    'break_start' => '13:00',
                    'break_end' => '14:00',
                    'closures' => [],
                ],
            ],
        );

        $configuration = app(ClinicAvailabilityService::class)->configuration();

        $this->assertSame([
            ['days' => [1, 2, 3, 4, 5], 'hours' => ['08:00', '09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00']],
        ], $configuration['groups']);
    }

    public function test_a_day_cannot_be_assigned_to_more_than_one_group(): void
    {
        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('groups', [
                ['days' => [1, 2, 3, 4, 5], 'hours' => ['08:00']],
                ['days' => [5, 6], 'hours' => ['10:00']],
            ])
            ->call('save')
            ->assertHasErrors('groups.1.days');
    }

    public function test_overlapping_manual_slots_are_rejected(): void
    {
        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('groups', [
                ['days' => [1], 'hours' => ['09:00', '09:30']],
            ])
            ->call('save')
            ->assertHasErrors('groups.0.hours');
    }

    public function test_invalid_closure_ranges_cannot_be_saved(): void
    {
        $tomorrow = today()->addDay()->toDateString();
        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('closures', [[
                'start_date' => today()->addDays(3)->toDateString(),
                'end_date' => $tomorrow,
                'reason' => 'Rango inválido',
            ]])
            ->call('save')
            ->assertHasErrors('closures.0.end_date');
    }

    public function test_schedule_changes_are_rejected_when_they_conflict_with_future_appointments(): void
    {
        $appointmentDate = $this->nextWeekday()->addWeek();
        $this->createAppointment($appointmentDate->toDateString(), '16:00');

        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('groups', [[
                'days' => [1, 2, 3, 4, 5],
                'hours' => ['08:00', '09:00', '10:00', '11:00', '12:00', '14:00', '15:00'],
            ]])
            ->call('save')
            ->assertSet('conflicts.0.start', $appointmentDate->format('d/m/Y').' 16:00');

        $this->assertSame(
            ClinicAvailabilityService::DEFAULT_CONFIGURATION,
            Setting::query()->where('key', ClinicAvailabilityService::SETTING_KEY)->firstOrFail()->value,
        );
    }

    public function test_duration_change_is_rejected_if_it_conflicts_with_a_future_appointment(): void
    {
        $appointmentDate = $this->nextWeekday()->addWeek();
        $this->createAppointment($appointmentDate->toDateString(), '08:00');

        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('durationMinutes', 120)
            ->set('groups', [[
                'days' => [1, 2, 3, 4, 5],
                'hours' => ['08:00', '10:00', '13:00', '15:00'],
            ]])
            ->call('save')
            ->assertSet('conflicts.0.start', $appointmentDate->format('d/m/Y').' 08:00');

        $this->assertSame(
            ClinicAvailabilityService::DEFAULT_CONFIGURATION,
            Setting::query()->where('key', ClinicAvailabilityService::SETTING_KEY)->firstOrFail()->value,
        );
    }

    public function test_schedule_page_requires_its_view_permission(): void
    {
        $user = User::create([
            'email' => 'unautorizado@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/admin/availability')
            ->assertForbidden();

        $this->actingAs($this->scheduler)
            ->get('/admin/availability')
            ->assertOk();
    }

    public function test_local_demo_seeder_does_not_overwrite_admin_managed_availability(): void
    {
        $customConfiguration = [
            'groups' => [['days' => [1], 'hours' => ['07:30']]],
            'duration_minutes' => 60,
            'closures' => [],
        ];
        Setting::query()->updateOrCreate(
            ['key' => ClinicAvailabilityService::SETTING_KEY],
            ['type' => 'array', 'value' => $customConfiguration, 'description' => 'Configuración elegida'],
        );

        app(CatalogosClinicosSeeder::class)->run();

        $this->assertSame(
            $customConfiguration,
            Setting::query()->where('key', ClinicAvailabilityService::SETTING_KEY)->firstOrFail()->value,
        );
    }

    private function nextWeekday(): CarbonInterface
    {
        $date = today()->addDay();

        while ($date->isWeekend()) {
            $date->addDay();
        }

        return $date;
    }

    private function createAppointment(string $date, string $time): Cita
    {
        $paciente = Paciente::create([
            'nombres' => 'Paciente',
            'apellidos' => 'Con cita',
            'telefono' => '5555-1234',
        ]);
        $inicio = Carbon::parse("{$date} {$time}");

        return $paciente->expediente()->create()->citas()->create([
            'tipo_cita' => 'primera_vez',
            'estado' => 'confirmada',
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addHour(),
        ]);
    }

}
