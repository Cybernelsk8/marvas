<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Permission;
use App\Traits\DataTable;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Roles extends Component
{
    use DataTable;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'align' => 'center'],
        ['index' => 'name', 'label' => 'Role'],
        ['index' => 'actions', 'label' => '', 'width' => '100px'],
    ];

    public array $role = [
        'name' => null,
        'permissions' => [],
    ];

    public ?string $search_permissions = null;

    #[Computed]
    public function rows()
    {
        $query = Role::when($this->search, function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('id', $this->search);
        })->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->per_page ?? 10);
    }

    public function render()
    {
        $all_permissions = Permission::query()
            ->when($this->search_permissions, function ($query) {
                $query->where('name', 'like', '%' . $this->search_permissions . '%');
            })
            ->orderBy('name')
            ->get()
            ->groupBy(fn($permission) => $this->resolvePermissionModule($permission->name));

        return view('livewire.admin.roles', compact('all_permissions'));
    }

    public function store(): void
    {
        $this->validate([
            'role.name' => 'required|string|max:255|unique:roles,name',
        ]);

        try {
            $role = Role::create([
                'name' => $this->normalizeName($this->role['name']),
            ]);

            $selectedPermissions = $this->role['permissions'] ?? [];
            if (! empty($selectedPermissions)) {
                $role->permissions()->sync($selectedPermissions);
            }

            Flux::toast(text: 'Role creado exitosamente.', variant: 'success');
            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Error al crear el role.', variant: 'danger');
        }
    }

    public function edit(int $id): void
    {
        $role = Role::findOrFail($id);

        $this->role = [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('id')->toArray(),
        ];

        Flux::modal('editRole')->show();
    }

    public function update(): void
    {
        $this->validate([
            'role.name' => 'required|string|max:255|unique:roles,name,' . ($this->role['id'] ?? 0),
        ]);

        try {
            $role = Role::findOrFail($this->role['id']);
            $newName = $this->normalizeName($this->role['name']);
            $selectedPermissions = array_values(array_unique($this->role['permissions'] ?? []));
            $currentPermissions = $role->permissions->pluck('id')->toArray();

            $hasChanges = $role->name !== $newName || $currentPermissions !== $selectedPermissions;

            if ($hasChanges) {
                $role->name = $newName;
                $role->save();
                $role->permissions()->sync($selectedPermissions);
                Flux::toast(text: 'Role actualizado exitosamente.', variant: 'success');
            }

            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Error al actualizar el role.', variant: 'danger');
        }
    }

    public function delete(int $id): void
    {
        $this->role = Role::findOrFail($id)->only(['id', 'name']);
        Flux::modal('deleteRole')->show();
    }

    public function destroy(): void
    {
        try {
            $role = Role::findOrFail($this->role['id']);
            $role->delete();

            Flux::toast(text: 'Role eliminado exitosamente.', variant: 'success');
            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Error al eliminar el role.', variant: 'danger');
        }
    }

    public function resetData(): void
    {
        $this->reset(['role', 'search_permissions']);
        $this->resetValidation();
        Flux::modals()->close();
    }

    public function resolvePermissionModule(?string $permissionName): string
    {
        if (empty($permissionName)) {
            return 'General';
        }

        return Str::before($permissionName, '.') ?: 'General';
    }

    protected function normalizeName(?string $value): ?string
    {
        return ! empty($value)
            ? trim($value)
            : null;
    }
}
