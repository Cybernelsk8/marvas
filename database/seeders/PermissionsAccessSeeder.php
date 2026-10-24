<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Registro central de TODOS los permisos (visualización de páginas y acciones).
 * Agregar aquí cada permiso que se cree junto con una página o funcionalidad.
 * Es idempotente: puede ejecutarse varias veces sin duplicar datos.
 */
class PermissionsAccessSeeder extends Seeder
{
    /** Permisos para ver páginas, con formato `page.view.{pagina}`. */
    private const PAGE_PERMISSIONS = [
        // Administración
        'page.view.admin',
        'page.view.users',
        'page.view.pages',
        'page.view.roles',
        'page.view.permissions',
        // Recepción
        'page.view.requests',
        // Agenda
        'page.view.schedule',
        // Pacientes
        'page.view.patients',
        // Catálogos clínicos
        'page.view.branches',
        'page.view.specialties',
        'page.view.professionals',
    ];

    /** Permisos para acciones, con formato `{modulo}.{accion}`. */
    private const ACTION_PERMISSIONS = [
        // Usuarios
        'users.list',
        'users.store',
        'users.edit',
        'users.disabled',
        'users.reset.password',
        'users.restore',
        'users.assign.permissions',
        // Páginas
        'pages.list',
        'pages.store',
        'pages.edit',
        'pages.disabled',
        // Roles
        'roles.list',
        'roles.store',
        'roles.edit',
        'roles.delete',
        // Permisos
        'permissions.list',
        'permissions.store',
        'permissions.edit',
        'permissions.delete',
        // Solicitudes
        'requests.edit',
        'requests.confirm',
        'requests.reschedule',
        'requests.cancel',
        // Agenda
        'schedule.edit',
        'appointments.create',
        // Pacientes
        'patients.create',
        'patients.edit',
        'patients.merge',
        'patients.clinical.view',
        'patients.documents.view',
        'patients.billing.view',
        // Consultas
        'consultations.create',
        'consultations.edit',
        'consultations.close',
        'consultations.reopen',
        // Catálogos clínicos
        'clinical-catalogs.manage',
    ];

    /** Permisos por rol: ['Rol' => ['permiso', ...]]. El rol se crea si no existe. */
    private const ROLE_PERMISSIONS = [
        'Recepcionista' => [
            'page.view.requests',
            'requests.edit',
            'requests.confirm',
            'requests.reschedule',
            'requests.cancel',
            'page.view.patients',
            'patients.create',
            'patients.edit',
            'patients.merge',
            'page.view.schedule',
            'appointments.create',
        ],
    ];

    public function run(): void
    {
        $permissions = [...self::PAGE_PERMISSIONS, ...self::ACTION_PERMISSIONS];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Sysadmin recibe todos los permisos si el rol ya existe.
        Role::query()->where('name', 'Sysadmin')->first()?->givePermissionTo($permissions);

        foreach (self::ROLE_PERMISSIONS as $roleName => $rolePermissions) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->givePermissionTo($rolePermissions);
        }
    }
}
