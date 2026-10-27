<?php

namespace Tests\Feature;

use App\Models\RegionCorporal;
use App\Models\ZonaDolor;
use Database\Seeders\RegionesCorporalesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PainMapSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_tables_do_not_exist(): void
    {
        $this->assertFalse(Schema::hasTable('informe_sistemas'));
        $this->assertFalse(Schema::hasTable('test_intoxicacion'));
    }

    public function test_region_catalog_seeds_anterior_and_posterior_regions(): void
    {
        $this->seed(RegionesCorporalesSeeder::class);

        $this->assertSame(12, RegionCorporal::where('vista', 'anterior')->count());
        $this->assertSame(8, RegionCorporal::where('vista', 'posterior')->count());
    }

    public function test_nivel_is_derived_from_eva(): void
    {
        $this->assertSame('sin_dolor', ZonaDolor::nivelDesdeEva(0));
        $this->assertSame('leve', ZonaDolor::nivelDesdeEva(3));
        $this->assertSame('moderado', ZonaDolor::nivelDesdeEva(4));
        $this->assertSame('intenso', ZonaDolor::nivelDesdeEva(8));
        $this->assertSame('muy_intenso', ZonaDolor::nivelDesdeEva(10));
    }
}
