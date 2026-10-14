<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Availability;
use App\Models\Admin\Setting;
use App\Models\Admin\User;
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

    public function test_authorized_user_can_update_global_hours_and_the_values_are_sorted(): void
    {
        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('hours', ['15:00', '09:00', '12:30'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('hours', ['09:00', '12:30', '15:00']);

        $this->assertSame(
            ['09:00', '12:30', '15:00'],
            Setting::query()->where('key', 'horarios_citas')->firstOrFail()->value,
        );
    }

    public function test_duplicate_or_empty_hours_cannot_be_saved(): void
    {
        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('hours', ['09:00', '09:00'])
            ->call('save')
            ->assertHasErrors(['hours.1']);

        $this->assertSame(
            ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'],
            Setting::query()->where('key', 'horarios_citas')->firstOrFail()->value,
        );

        Livewire::actingAs($this->scheduler)
            ->test(Availability::class)
            ->set('hours', [''])
            ->call('save')
            ->assertHasErrors(['hours.0']);
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

    public function test_local_demo_seeder_does_not_overwrite_hours_managed_by_admin(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'horarios_citas'],
            ['type' => 'array', 'value' => ['08:30'], 'description' => 'Configuración elegida'],
        );

        app(CatalogosClinicosSeeder::class)->run();

        $this->assertSame(
            ['08:30'],
            Setting::query()->where('key', 'horarios_citas')->firstOrFail()->value,
        );
    }
}
