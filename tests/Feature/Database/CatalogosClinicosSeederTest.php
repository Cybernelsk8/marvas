<?php

namespace Tests\Feature\Database;

use App\Models\Admin\Page;
use App\Models\Admin\Setting;
use App\Models\CategoriaInventario;
use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Sucursal;
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

        $this->assertSame(6, Especialidad::query()->count());
        $this->assertSame(6, CategoriaInventario::query()->count());
        $this->assertSame(1, Sucursal::query()->count());
        $this->assertSame(6, Profesional::query()->count());
        $this->assertDatabaseCount('profesional_especialidades', 6);
        $this->assertDatabaseCount('profesional_sucursales', 6);
        $this->assertSame(
            ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'],
            Setting::query()->where('key', 'horarios_citas')->firstOrFail()->value,
        );
        $this->assertSame('child', Page::query()->where('label', 'Solicitudes')->value('type'));
        $this->assertSame(
            ['page.view.requests', 'page.view.schedule', 'requests.cancel', 'requests.confirm', 'requests.edit', 'requests.reschedule'],
            Role::findByName('Recepcionista')->permissions->pluck('name')->sort()->values()->all(),
        );
        $this->assertSame('admin.schedule', Page::query()->where('label', 'Agenda de citas')->value('route'));
    }

    public function test_demo_staff_accounts_are_not_seeded_outside_local_or_testing(): void
    {
        $this->app['env'] = 'production';

        app(CatalogosClinicosSeeder::class)->run();

        $this->assertSame(6, Especialidad::query()->count());
        $this->assertSame(6, CategoriaInventario::query()->count());
        $this->assertSame(0, Sucursal::query()->count());
        $this->assertSame(0, Profesional::query()->count());
        $this->assertSame(0, Setting::query()->count());
    }
}
