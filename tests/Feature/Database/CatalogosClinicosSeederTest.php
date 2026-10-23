<?php

namespace Tests\Feature\Database;

use App\Models\Admin\Page;
use App\Models\Admin\Setting;
use App\Models\CategoriaInventario;
use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Sucursal;
use App\Services\ClinicAvailabilityService;
use Database\Seeders\CatalogosClinicosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CatalogosClinicosSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_repeatable_catalogues_and_local_demo_relations(): void
    {
        $seeder = app(CatalogosClinicosSeeder::class);

        $seeder->run();
        $seeder->run();

        $this->assertSame(7, Especialidad::query()->count());
        $this->assertSame(6, CategoriaInventario::query()->count());
        $this->assertSame(1, Sucursal::query()->count());
        $this->assertSame(7, Profesional::query()->count());
        $this->assertDatabaseCount('profesional_especialidades', 7);
        $this->assertDatabaseCount('profesional_sucursales', 7);
        $this->assertDatabaseHas('sucursal_evaluadores_mbi', [
            'sucursal_id' => Sucursal::query()->value('id'),
            'profesional_id' => Profesional::query()->where('nombre_completo', 'Profesional de demostración - Evaluador MBI')->value('id'),
        ]);
        $this->assertSame(
            ClinicAvailabilityService::DEFAULT_CONFIGURATION,
            Setting::query()->where('key', ClinicAvailabilityService::SETTING_KEY)->firstOrFail()->value,
        );
        $this->assertSame('child', Page::query()->where('label', 'Solicitudes')->value('type'));
        $this->assertSame(
            ['page.view.patients', 'page.view.requests', 'page.view.schedule', 'patients.create', 'patients.edit', 'patients.merge', 'requests.cancel', 'requests.confirm', 'requests.edit', 'requests.reschedule'],
            Role::findByName('Recepcionista')->permissions->pluck('name')->sort()->values()->all(),
        );
        $this->assertDatabaseHas('permissions', ['name' => 'patients.clinical.view', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'patients.documents.view', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'patients.billing.view', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'consultations.create', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'consultations.edit', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'consultations.close', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'consultations.reopen', 'guard_name' => 'web']);
        $this->assertFalse(Role::findByName('Recepcionista')->hasPermissionTo('patients.clinical.view'));
        $this->assertFalse(Role::findByName('Recepcionista')->hasPermissionTo('patients.documents.view'));
        $this->assertFalse(Role::findByName('Recepcionista')->hasPermissionTo('patients.billing.view'));
        $this->assertFalse(Role::findByName('Recepcionista')->hasPermissionTo('consultations.create'));
        $this->assertFalse(Role::findByName('Recepcionista')->hasPermissionTo('consultations.close'));
        $this->assertFalse(Role::findByName('Recepcionista')->hasPermissionTo('consultations.reopen'));
        $this->assertSame('admin.schedule', Page::query()->where('label', 'Agenda de citas')->value('route'));
        $this->assertSame('admin.patients.index', Page::query()->where('label', 'Directorio de pacientes')->value('route'));
    }

    public function test_demo_staff_accounts_are_not_seeded_outside_local_or_testing(): void
    {
        $this->app['env'] = 'production';

        app(CatalogosClinicosSeeder::class)->run();

        $this->assertSame(7, Especialidad::query()->count());
        $this->assertSame(6, CategoriaInventario::query()->count());
        $this->assertSame(0, Sucursal::query()->count());
        $this->assertSame(0, Profesional::query()->count());
        $this->assertSame(0, Setting::query()->count());
    }
}
