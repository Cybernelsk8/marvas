<?php

namespace Database\Seeders;

use App\Models\Admin\Page;
use Illuminate\Database\Seeder;

class SolicitudesAccessSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionsAccessSeeder::class);

        $parent = Page::withTrashed()->firstOrNew(['label' => 'Recepción']);
        $parent->fill([
            'icon' => 'phone',
            'route' => null,
            'order' => 2,
            'type' => 'parent',
            'permission_name' => null,
            'page_id' => null,
        ]);
        if ($parent->exists && $parent->trashed()) {
            $parent->restore();
        }
        $parent->save();

        $page = Page::withTrashed()->firstOrNew(['label' => 'Solicitudes']);
        $page->fill([
            'icon' => 'calendar-days',
            'route' => 'solicitudes',
            'order' => 1,
            'type' => 'child',
            'permission_name' => 'page.view.requests',
            'page_id' => $parent->id,
        ]);
        if ($page->exists && $page->trashed()) {
            $page->restore();
        }
        $page->save();
    }
}
