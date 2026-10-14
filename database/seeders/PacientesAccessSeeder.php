<?php

namespace Database\Seeders;

use App\Models\Admin\Page;
use App\Models\Admin\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PacientesAccessSeeder extends Seeder
{
    public function run(): void
    {
        $permissionNames = [
            'page.view.patients',
            'patients.create',
            'patients.edit',
            'patients.merge',
        ];
        $sectionPermissions = [
            'patients.clinical.view',
            'patients.documents.view',
            'patients.billing.view',
        ];
        $consultationPermissions = [
            'consultations.create',
            'consultations.edit',
            'consultations.close',
            'consultations.reopen',
        ];

        foreach ([...$permissionNames, ...$sectionPermissions, ...$consultationPermissions] as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $parent = Page::withTrashed()->firstOrNew(['label' => 'Pacientes']);
        $parent->fill([
            'icon' => 'users',
            'route' => null,
            'order' => 4,
            'type' => 'parent',
            'permission_name' => null,
            'page_id' => null,
        ]);
        if ($parent->exists && $parent->trashed()) {
            $parent->restore();
        }
        $parent->save();

        $page = Page::withTrashed()->firstOrNew(['label' => 'Directorio de pacientes']);
        $page->fill([
            'icon' => 'identification',
            'route' => 'admin.patients.index',
            'order' => 1,
            'type' => 'child',
            'permission_name' => 'page.view.patients',
            'page_id' => $parent->id,
        ]);
        if ($page->exists && $page->trashed()) {
            $page->restore();
        }
        $page->save();

        Role::query()
            ->whereIn('name', ['Sysadmin', 'Recepcionista'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissionNames));
    }
}
