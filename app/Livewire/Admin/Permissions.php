<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Permission;
use App\Traits\DataTable;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Permissions extends Component
{
    use DataTable;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'align' => 'center'],
        ['index' => 'name', 'label' => 'Permiso'],
        ['index' => 'actions', 'label' => '', 'width' => '100px'],
    ];

    public array $permission = [
        'name' => null,
    ];

    #[Computed]
    public function rows()
    {
        return Permission::filterAdvance($this->headers, [
            'search' => $this->search,
            'sort' => [
                'field' => $this->sortBy,
                'direction' => $this->sortDirection,
            ],
            'filters' => $this->processFilters(),
        ])->paginate($this->per_page ?? 10);
    }

    public function mount(): void
    {
        $this->sortBy = 'name';
    }

    public function render()
    {
        return view('livewire.admin.permissions');
    }

    public function store(): void
    {
        $this->validate([
            'permission.name' => 'required|string|max:255|unique:permissions,name',
        ]);

        try {
            Permission::create([
                'name' => $this->normalizeName($this->permission['name']),
                'guard_name' => 'web',
            ]);

            Flux::toast(text: 'Permiso creado exitosamente.', variant: 'success');
            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Error al crear el permiso.', variant: 'danger');
        }
    }

    public function edit(int $id): void
    {
        $this->permission = Permission::findOrFail($id)->only(['id', 'name']);
        Flux::modal('editPermission')->show();
    }

    public function update(): void
    {
        $this->validate([
            'permission.name' => 'required|string|max:255|unique:permissions,name,' . ($this->permission['id'] ?? 0),
        ]);

        try {
            $permission = Permission::findOrFail($this->permission['id']);
            $newName = $this->normalizeName($this->permission['name']);

            if ($permission->name !== $newName) {
                $permission->name = $newName;
                $permission->save();
                Flux::toast(text: 'Permiso actualizado exitosamente.', variant: 'success');
            }

            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Error al actualizar el permiso.', variant: 'danger');
        }
    }

    public function delete(int $id): void
    {
        $this->permission = Permission::findOrFail($id)->only(['id', 'name']);
        Flux::modal('deletePermission')->show();
    }

    public function destroy(): void
    {
        try {
            $permission = Permission::findOrFail($this->permission['id']);
            $permission->delete();

            Flux::toast(text: 'Permiso eliminado exitosamente.', variant: 'success');
            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Error al eliminar el permiso.', variant: 'danger');
        }
    }

    public function resetData(): void
    {
        $this->reset('permission');
        $this->resetValidation();
        Flux::modals()->close();
    }

    protected function normalizeName(?string $value): ?string
    {
        return ! empty($value)
            ? trim($value)
            : null;
    }
}
