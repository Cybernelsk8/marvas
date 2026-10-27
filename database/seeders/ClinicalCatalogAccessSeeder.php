<?php

namespace Database\Seeders;

use App\Models\Admin\Page;
use Illuminate\Database\Seeder;

class ClinicalCatalogAccessSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionsAccessSeeder::class);

        $parent = $this->savePage('Catálogos clínicos', [
            'icon' => 'building-office-2',
            'route' => null,
            'order' => 5,
            'type' => 'parent',
            'permission_name' => null,
            'page_id' => null,
        ]);

        foreach ([
            ['label' => 'Sedes', 'route' => 'admin.branches', 'permission' => 'page.view.branches', 'order' => 1, 'icon' => 'building-office'],
            ['label' => 'Especialidades', 'route' => 'admin.specialties', 'permission' => 'page.view.specialties', 'order' => 2, 'icon' => 'clipboard-document-list'],
            ['label' => 'Profesionales', 'route' => 'admin.professionals', 'permission' => 'page.view.professionals', 'order' => 3, 'icon' => 'user-group'],
            ['label' => 'Regiones corporales', 'route' => 'admin.body-regions', 'permission' => 'page.view.body-regions', 'order' => 4, 'icon' => 'map'],
        ] as $page) {
            $this->savePage($page['label'], [
                'icon' => $page['icon'],
                'route' => $page['route'],
                'order' => $page['order'],
                'type' => 'child',
                'permission_name' => $page['permission'],
                'page_id' => $parent->id,
            ]);
        }
    }

    private function savePage(string $label, array $attributes): Page
    {
        $page = Page::withTrashed()->firstOrNew(['label' => $label]);
        $page->fill($attributes);
        if ($page->exists && $page->trashed()) {
            $page->restore();
        }
        $page->save();

        return $page;
    }
}
