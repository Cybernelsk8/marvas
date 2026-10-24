<?php

namespace Database\Seeders;

use App\Models\Admin\Page;
use Illuminate\Database\Seeder;

class AgendaAccessSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionsAccessSeeder::class);

        $parent = Page::withTrashed()->firstOrNew(['label' => 'Agenda']);
        $parent->fill([
            'icon' => 'calendar-days',
            'route' => null,
            'order' => 3,
            'type' => 'parent',
            'permission_name' => null,
            'page_id' => null,
        ]);
        if ($parent->exists && $parent->trashed()) {
            $parent->restore();
        }
        $parent->save();

        $agendaPage = Page::withTrashed()->firstOrNew(['label' => 'Agenda de citas']);
        $agendaPage->fill([
            'icon' => 'calendar',
            'route' => 'admin.schedule',
            'order' => 1,
            'type' => 'child',
            'permission_name' => 'page.view.schedule',
            'page_id' => $parent->id,
        ]);
        if ($agendaPage->exists && $agendaPage->trashed()) {
            $agendaPage->restore();
        }
        $agendaPage->save();

        $availabilityPage = Page::withTrashed()->firstOrNew(['label' => 'Disponibilidad']);
        $availabilityPage->fill([
            'icon' => 'clock',
            'route' => 'admin.availability',
            'order' => 2,
            'type' => 'child',
            'permission_name' => 'page.view.schedule',
            'page_id' => $parent->id,
        ]);
        if ($availabilityPage->exists && $availabilityPage->trashed()) {
            $availabilityPage->restore();
        }
        $availabilityPage->save();
    }
}
