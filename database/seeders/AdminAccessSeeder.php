<?php

namespace Database\Seeders;

use App\Models\Admin\Area;
use App\Models\Admin\Page;
use Illuminate\Database\Seeder;

class AdminAccessSeeder extends Seeder
{
    public function run(): void
    {
        Area::firstOrCreate(['name' => 'Gerencia']);

        $adminPage = Page::withTrashed()->firstOrNew(['label' => 'Admin']);
        $adminPage->fill([
            'icon' => 'square-3-stack-3d',
            'type' => 'parent',
            'order' => 1,
            'permission_name' => 'page.view.admin',
            'route' => null,
            'page_id' => null,
        ]);
        if ($adminPage->exists && $adminPage->trashed()) {
            $adminPage->restore();
        }
        $adminPage->save();

        $pages = [
            [
                'label' => 'Usuarios',
                'route' => 'admin.users.index',
                'icon' => 'users',
                'order' => 1,
                'permission_name' => 'page.view.users',
            ],
            [
                'label' => 'Paginas',
                'route' => 'admin.pages',
                'icon' => 'window',
                'order' => 2,
                'permission_name' => 'page.view.pages',
            ],
            [
                'label' => 'Roles',
                'route' => 'admin.roles',
                'icon' => 'tag',
                'order' => 3,
                'permission_name' => 'page.view.roles',
            ],
            [
                'label' => 'Permisos',
                'route' => 'admin.permissions',
                'icon' => 'lock-closed',
                'order' => 4,
                'permission_name' => 'page.view.permissions',
            ],
        ];

        foreach ($pages as $pageData) {
            $page = Page::withTrashed()->firstOrNew(['label' => $pageData['label']]);
            $page->fill([
                'route' => $pageData['route'],
                'icon' => $pageData['icon'],
                'page_id' => $adminPage->id,
                'type' => 'child',
                'order' => $pageData['order'],
                'permission_name' => $pageData['permission_name'],
            ]);
            if ($page->exists && $page->trashed()) {
                $page->restore();
            }
            $page->save();
        }
    }
}
