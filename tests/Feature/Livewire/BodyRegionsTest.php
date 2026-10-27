<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Clinical\BodyRegions;
use App\Models\Admin\User;
use App\Models\RegionCorporal;
use Database\Seeders\ClinicalCatalogAccessSeeder;
use Database\Seeders\RegionesCorporalesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BodyRegionsTest extends TestCase
{
    use RefreshDatabase;

    private User $sysadmin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Sysadmin', 'guard_name' => 'web']);
        app(ClinicalCatalogAccessSeeder::class)->run();
        $this->seed(RegionesCorporalesSeeder::class);
        $this->sysadmin = User::factory()->create();
        $this->sysadmin->assignRole('Sysadmin');
    }

    public function test_seeded_regions_have_outlines_per_side_and_reseeding_keeps_manual_edits(): void
    {
        $pectorales = RegionCorporal::where('codigo', 'pectorales')->firstOrFail();
        $this->assertEqualsCanonicalizing(['derecha', 'izquierda'], array_keys($pectorales->trazos));
        $this->assertSame(['central'], array_keys(RegionCorporal::where('codigo', 'lumbar')->first()->trazos));
        $this->assertSame(0, RegionCorporal::whereNull('trazos')->count());

        $pectorales->update(['trazos' => ['derecha' => 'M1 1 L2 2 L3 1 Z']]);
        $this->seed(RegionesCorporalesSeeder::class);
        $this->assertSame(['derecha' => 'M1 1 L2 2 L3 1 Z'], $pectorales->fresh()->trazos);
    }

    public function test_sysadmin_creates_edits_and_traces_a_region(): void
    {
        Livewire::actingAs($this->sysadmin)
            ->test(BodyRegions::class)
            ->set('nombre', 'Serrato mayor')
            ->set('vista', 'anterior')
            ->set('trazos', ['derecha' => 'M10 10 L50 10 L30 60 Z', 'central' => 'M1 1 L2 2 L3 1 Z'])
            ->call('save')
            ->assertHasNoErrors();

        $region = RegionCorporal::where('codigo', 'serrato_mayor')->firstOrFail();
        $this->assertSame(['derecha' => 'M10 10 L50 10 L30 60 Z'], $region->trazos);

        Livewire::actingAs($this->sysadmin)
            ->test(BodyRegions::class)
            ->call('edit', $region->id)
            ->set('trazos', ['derecha' => 'M10 10 L80 10 L30 90 Z'])
            ->call('save')
            ->assertHasNoErrors()
            ->call('toggleActive', $region->id);

        $this->assertSame('M10 10 L80 10 L30 90 Z', $region->fresh()->trazos['derecha']);
        $this->assertFalse($region->fresh()->activo);
    }

    public function test_invalid_path_and_duplicate_code_are_rejected(): void
    {
        Livewire::actingAs($this->sysadmin)
            ->test(BodyRegions::class)
            ->set('nombre', 'Otra')
            ->set('trazos', ['derecha' => '<script>'])
            ->call('save')
            ->assertHasErrors('trazos.derecha');

        Livewire::actingAs($this->sysadmin)
            ->test(BodyRegions::class)
            ->set('nombre', 'Pectorales')
            ->set('codigo', 'pectorales')
            ->call('save')
            ->assertHasErrors('codigo');
    }

    public function test_user_without_permission_cannot_open_the_catalog(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.body-regions'))
            ->assertForbidden();
    }
}
