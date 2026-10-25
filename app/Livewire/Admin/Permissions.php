<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Permission;
use App\Traits\DataTable;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Attributes\Title;

/** Componente Livewire para administrar los permisos de acceso. */
#[Title('Permisos')]
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

    /**
     * Lista permisos aplicando búsqueda, orden, filtros y paginación.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    // Lista permisos aplicando búsqueda, orden, filtros y paginación.
    //   Ejemplo: Desde Blade: {{ $this->rows->total() }}.
    //   Resultado: La tabla recibe la página filtrada y conserva el total para la paginación.
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

    /**
     * Inicializa el orden por nombre para facilitar la localización de permisos.
     *
     * @return void
     */
    // Inicializa el orden por nombre para facilitar la localización de permisos.
    //   Ejemplo: Livewire invoca este hook al montar el componente.
    //   Resultado: El componente queda autorizado y configurado con sus valores iniciales.
    public function mount(): void
    {
        $this->sortBy = 'name';
    }

    /**
     * Devuelve la vista administrativa de permisos.
     *
     * @return \Illuminate\Contracts\View\View
     */
    // Devuelve la vista administrativa de permisos.
    //   Ejemplo: Livewire lo invoca al mostrar o actualizar el componente.
    //   Resultado: Livewire entrega la vista con los datos preparados para sus controles.
    public function render()
    {
        return view('livewire.admin.permissions');
    }

    /**
     * Valida y crea un permiso normalizando el nombre y asignando el guard web.
     *
     * @return void
     */
    // Valida y crea un permiso normalizando el nombre y asignando el guard web.
    //   Ejemplo: Desde la vista: wire:click="store"
    //   Resultado: El registro se crea y la interfaz confirma el resultado.
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

    /**
     * Carga los campos editables del permiso seleccionado y abre el modal.
     *
     * @param  int $id
     * @return void
     */
    // Carga los campos editables del permiso seleccionado y abre el modal.
    //   Ejemplo: Desde la vista: wire:click="edit({{ $row->id }})"
    //   Resultado: El formulario se abre con los datos del registro seleccionado.
    public function edit(int $id): void
    {
        $this->permission = Permission::findOrFail($id)->only(['id', 'name']);
        Flux::modal('editPermission')->show();
    }

    /**
     * Valida y guarda el nombre del permiso cuando cambió; luego limpia el formulario.
     *
     * @return void
     */
    // Valida y guarda el nombre del permiso cuando cambió; luego limpia el formulario.
    //   Ejemplo: Desde la vista: wire:click="update"
    //   Resultado: Los cambios válidos quedan guardados y la interfaz confirma el resultado.
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

    /**
     * Carga el permiso seleccionado y abre su diálogo de eliminación.
     *
     * @param  int $id
     * @return void
     */
    // Carga el permiso seleccionado y abre su diálogo de eliminación.
    //   Ejemplo: Desde la vista: wire:click="delete({{ $row->id }})"
    //   Resultado: El diálogo identifica el registro pendiente de eliminación.
    public function delete(int $id): void
    {
        $this->permission = Permission::findOrFail($id)->only(['id', 'name']);
        Flux::modal('deletePermission')->show();
    }

    /**
     * Elimina el permiso confirmado y muestra el resultado de la operación.
     *
     * @return void
     */
    // Elimina el permiso confirmado y muestra el resultado de la operación.
    //   Ejemplo: Desde la vista: wire:click="destroy"
    //   Resultado: El registro se elimina y la interfaz informa el resultado.
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

    /**
     * Restablece el formulario, limpia errores de validación y cierra los modales.
     *
     * @return void
     */
    // Restablece el formulario, limpia errores de validación y cierra los modales.
    //   Ejemplo: Desde la vista: wire:click="resetData"
    //   Resultado: Los datos y errores del formulario se limpian y se cierran los modales.
    public function resetData(): void
    {
        $this->reset('permission');
        $this->resetValidation();
        Flux::modals()->close();
    }

    /**
     * Recorta espacios y convierte una entrada vacía en null para guardar un nombre consistente.
     *
     * @param  ?string $value
     * @return ?string
     */
    // Recorta espacios y convierte una entrada vacía en null para guardar un nombre consistente.
    //   Ejemplo: Uso interno: $this->normalizeName($value)
    //   Resultado: El texto queda sin espacios externos; una entrada vacía se convierte en null.
    protected function normalizeName(?string $value): ?string
    {
        return ! empty($value)
            ? trim($value)
            : null;
    }
}
