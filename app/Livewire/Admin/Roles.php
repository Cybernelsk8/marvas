<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Permission;
use App\Traits\DataTable;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Livewire\Attributes\Title;

/** Componente Livewire para gestionar roles y permisos asociados. */
#[Title('Roles')]
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

    /**
     * Filtra roles por búsqueda, aplica el orden y pagina los resultados.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    // Filtra roles por búsqueda, aplica el orden y pagina los resultados.
    //   Ejemplo: Desde Blade: {{ $this->rows->total() }}.
    //   Resultado: La tabla recibe la página filtrada y conserva el total para la paginación.
    #[Computed]
    public function rows()
    {
        $query = Role::when($this->search, function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('id', $this->search);
        })->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->per_page ?? 10);
    }

    /**
     * Carga permisos filtrados y los agrupa por módulo para el formulario de roles.
     *
     * @return \Illuminate\Contracts\View\View
     */
    // Carga permisos filtrados y los agrupa por módulo para el formulario de roles.
    //   Ejemplo: Livewire lo invoca al mostrar o actualizar el componente.
    //   Resultado: Livewire entrega la vista con los datos preparados para sus controles.
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

    /**
     * Valida y crea un rol y sincroniza los permisos seleccionados.
     *
     * @return void
     */
    // Valida y crea un rol y sincroniza los permisos seleccionados.
    //   Ejemplo: Desde la vista: wire:click="store"
    //   Resultado: El registro se crea y la interfaz confirma el resultado.
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

    /**
     * Carga un rol y sus permisos para preparar el formulario de edición.
     *
     * @param  int $id
     * @return void
     */
    // Carga un rol y sus permisos para preparar el formulario de edición.
    //   Ejemplo: Desde la vista: wire:click="edit({{ $row->id }})"
    //   Resultado: El formulario se abre con los datos del registro seleccionado.
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

    /**
     * Actualiza el nombre del rol y sincroniza permisos cuando detecta cambios.
     *
     * @return void
     */
    // Actualiza el nombre del rol y sincroniza permisos cuando detecta cambios.
    //   Ejemplo: Desde la vista: wire:click="update"
    //   Resultado: Los cambios válidos quedan guardados y la interfaz confirma el resultado.
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

    /**
     * Carga el rol seleccionado y abre el modal de confirmación.
     *
     * @param  int $id
     * @return void
     */
    // Carga el rol seleccionado y abre el modal de confirmación.
    //   Ejemplo: Desde la vista: wire:click="delete({{ $row->id }})"
    //   Resultado: El diálogo identifica el registro pendiente de eliminación.
    public function delete(int $id): void
    {
        $this->role = Role::findOrFail($id)->only(['id', 'name']);
        Flux::modal('deleteRole')->show();
    }

    /**
     * Elimina el rol confirmado y comunica el resultado.
     *
     * @return void
     */
    // Elimina el rol confirmado y comunica el resultado.
    //   Ejemplo: Desde la vista: wire:click="destroy"
    //   Resultado: El registro se elimina y la interfaz informa el resultado.
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

    /**
     * Restablece los campos del rol y la búsqueda de permisos, limpia errores y cierra modales.
     *
     * @return void
     */
    // Restablece los campos del rol y la búsqueda de permisos, limpia errores y cierra modales.
    //   Ejemplo: Desde la vista: wire:click="resetData"
    //   Resultado: Los datos y errores del formulario se limpian y se cierran los modales.
    public function resetData(): void
    {
        $this->reset(['role', 'search_permissions']);
        $this->resetValidation();
        Flux::modals()->close();
    }

    /**
     * Obtiene el prefijo anterior al punto para agrupar permisos; usa General si no hay módulo.
     *
     * @param  ?string $permissionName
     * @return string
     */
    // Obtiene el prefijo anterior al punto para agrupar permisos; usa General si no hay módulo.
    //   Ejemplo: Uso interno: $this->resolvePermissionModule($permissionName)
    //   Resultado: "agenda.view" se agrupa como "agenda"; si falta prefijo, usa "General".
    public function resolvePermissionModule(?string $permissionName): string
    {
        if (empty($permissionName)) {
            return 'General';
        }

        return Str::before($permissionName, '.') ?: 'General';
    }

    /**
     * Recorta espacios y convierte una entrada vacía en null antes de guardar el nombre.
     *
     * @param  ?string $value
     * @return ?string
     */
    // Recorta espacios y convierte una entrada vacía en null antes de guardar el nombre.
    //   Ejemplo: Uso interno: $this->normalizeName($value)
    //   Resultado: El texto queda sin espacios externos; una entrada vacía se convierte en null.
    protected function normalizeName(?string $value): ?string
    {
        return ! empty($value)
            ? trim($value)
            : null;
    }
}
