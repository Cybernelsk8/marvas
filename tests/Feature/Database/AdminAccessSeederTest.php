<?php

namespace Tests\Feature\Database;

use App\Models\Admin\Page;
use Database\Seeders\AdminAccessSeeder;
use Database\Seeders\PermissionsAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_be_run_repeatedly_without_duplicate_permissions_or_menu_pages(): void
    {
        $seeder = app(AdminAccessSeeder::class);
        $permissions = app(PermissionsAccessSeeder::class);

        $permissions->run();
        $seeder->run();
        Page::query()->where('label', 'Roles')->firstOrFail()->delete();
        $permissions->run();
        $seeder->run();

        $this->assertDatabaseCount('areas', 1);
        $this->assertDatabaseCount('pages', 5);
        $this->assertDatabaseCount('permissions', 47);
        $this->assertNotSoftDeleted('pages', ['label' => 'Roles']);
        $this->assertDatabaseHas('pages', [
            'label' => 'Usuarios',
            'route' => 'admin.users.index',
            'page_id' => Page::query()->where('label', 'Admin')->value('id'),
        ]);
    }
}
