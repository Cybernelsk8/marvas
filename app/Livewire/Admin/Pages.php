<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Page;
use App\Models\Admin\Permission;
use App\Traits\DataTable;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Attributes\Title;

/** Componente Livewire para gestionar las páginas del menú administrativo. */
#[Title('Páginas')]
class Pages extends Component
{
    use DataTable;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'align' => 'center'],
        ['index' => 'label', 'label' => 'Página'],
        ['index' => 'icon', 'label' => 'Icono'],
        ['index' => 'route', 'label' => 'Ruta'],
        ['index' => 'order_label', 'label' => 'Orden'],
        ['index' => 'deleted_at', 'label' => 'Estado'],
        ['index' => 'parent.label', 'label' => 'Padre', 'hidden' => true],
        ['index' => 'type', 'label' => 'Tipo', 'align' => 'center'],
        ['index' => 'permission_name', 'label' => 'Permiso'],
        ['index' => 'actions', 'label' => '', 'width' => '100px'],
    ];

    public array $page = [
        'label' => null,
        'icon' => null,
        'route' => null,
        'order' => null,
        'type' => null,
        'page_id' => null,
        'permission_name' => null,
    ];

    /**
     * Consulta páginas con su padre y aplica búsqueda, orden, filtros y paginación.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    // Consulta páginas con su padre y aplica búsqueda, orden, filtros y paginación.
    //   Ejemplo: Desde Blade: {{ $this->rows->total() }}.
    //   Resultado: La tabla recibe la página filtrada y conserva el total para la paginación.
    #[Computed]
    public function rows()
    {
        $query = Page::with(['parent'])
            ->withTrashed()
            ->filterAdvance($this->headers, [
                'search' => $this->search,
                'sort' => [
                    'field' => $this->sortBy,
                    'direction' => $this->sortDirection,
                ],
                'filters' => $this->processFilters(),
            ]);

        return $query->paginate($this->per_page ?? 10);
    }

    /**
     * Hook de montaje sin inicialización adicional; Livewire lo ejecuta al crear el componente.
     *
     * @return void
     */
    // Hook de montaje sin inicialización adicional; Livewire lo ejecuta al crear el componente.
    //   Ejemplo: Livewire invoca este hook al montar el componente.
    //   Resultado: El componente queda autorizado y configurado con sus valores iniciales.
    public function mount(): void {}

    /**
     * Carga páginas y permisos para poblar los selectores de la vista.
     *
     * @return \Illuminate\Contracts\View\View
     */
    // Carga páginas y permisos para poblar los selectores de la vista.
    //   Ejemplo: Livewire lo invoca al mostrar o actualizar el componente.
    //   Resultado: Livewire entrega la vista con los datos preparados para sus controles.
    public function render()
    {
        $pages = Page::query()
            ->orderBy('label')
            ->get(['id', 'label', 'type']);

        $permissions = Permission::where('name', 'like', '%page.view%')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.pages', compact('pages', 'permissions'));
    }

    /**
     * Valida y crea una página aplicando valores predeterminados a campos opcionales.
     *
     * @return void
     */
    // Valida y crea una página aplicando valores predeterminados a campos opcionales.
    //   Ejemplo: Desde la vista: wire:click="store"
    //   Resultado: El registro se crea y la interfaz confirma el resultado.
    public function store(): void
    {
        $this->validate([
            'page.label' => 'required|string|max:255',
            'page.icon' => 'nullable|string|max:255',
            'page.route' => 'nullable|string|max:255',
            'page.order' => 'nullable|integer',
            'page.type' => 'required|in:header,parent,child,page',
            'page.page_id' => 'required_if:page.type,child|nullable|exists:pages,id',
            'page.permission_name' => 'nullable|string|exists:permissions,name',
        ]);

        try {
            Page::create([
                'label' => trim($this->page['label']),
                'icon' => $this->page['icon'] ?? 'question-mark-circle',
                'route' => $this->page['route'] ?? null,
                'order' => $this->page['order'] ?? null,
                'type' => $this->page['type'],
                'page_id' => $this->page['page_id'] ?? null,
                'permission_name' => $this->page['permission_name'] ?? null,
            ]);

            Flux::toast(text: 'Página creada con éxito.', variant: 'success');
            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Error al crear la página.', variant: 'danger');
        }
    }

    /**
     * Carga la página seleccionada, incluso si está desactivada, y abre su formulario.
     *
     * @param  int $id
     * @return void
     */
    // Carga la página seleccionada, incluso si está desactivada, y abre su formulario.
    //   Ejemplo: Desde la vista: wire:click="edit({{ $row->id }})"
    //   Resultado: El formulario se abre con los datos del registro seleccionado.
    public function edit(int $id): void
    {
        $this->page = Page::withTrashed()->findOrFail($id)->toArray();
        Flux::modal('editPage')->show();
    }

    /**
     * Valida y guarda la página editada y después limpia el formulario.
     *
     * @return void
     */
    // Valida y guarda la página editada y después limpia el formulario.
    //   Ejemplo: Desde la vista: wire:click="update"
    //   Resultado: Los cambios válidos quedan guardados y la interfaz confirma el resultado.
    public function update(): void
    {
        $this->validate([
            'page.label' => 'required|string|max:255',
            'page.icon' => 'nullable|string|max:255',
            'page.route' => 'nullable|string|max:255',
            'page.order' => 'nullable|integer',
            'page.type' => 'required|in:header,parent,child,page',
            'page.page_id' => 'required_if:page.type,child|nullable|exists:pages,id',
            'page.permission_name' => 'nullable|string|exists:permissions,name',
        ]);

        try {
            $page = Page::withTrashed()->findOrFail($this->page['id']);
            $page->fill([
                'label' => trim($this->page['label']),
                'icon' => $this->page['icon'] ?? 'question-mark-circle',
                'route' => $this->page['route'] ?? null,
                'order' => $this->page['order'] ?? null,
                'type' => $this->page['type'],
                'page_id' => $this->page['page_id'] ?? null,
                'permission_name' => $this->page['permission_name'] ?? null,
            ]);

            if ($page->isDirty()) {
                $page->save();
                Flux::toast(text: 'Página actualizada con éxito.', variant: 'success');
            }

            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(
                variant: 'danger',
                text: 'Error al actualizar la página.' . $th->getMessage()
            );
        }
    }

    /**
     * Carga una página y abre el diálogo de confirmación para desactivarla.
     *
     * @param  int $id
     * @return void
     */
    // Carga una página y abre el diálogo de confirmación para desactivarla.
    //   Ejemplo: Desde la vista: wire:click="disableItem({{ $row->id }})"
    //   Resultado: El diálogo queda abierto para confirmar el cambio de estado.
    public function disableItem(int $id): void
    {
        $this->page = Page::withTrashed()->findOrFail($id)->toArray();
        Flux::modal('disableItem')->show();
    }

    /**
     * Desactiva lógicamente la página confirmada para conservar su registro.
     *
     * @return void
     */
    // Desactiva lógicamente la página confirmada para conservar su registro.
    //   Ejemplo: Desde la vista: wire:click="disabled"
    //   Resultado: La página se desactiva y se muestra una notificación.
    public function disabled(): void
    {
        try {
            $page = Page::withTrashed()->findOrFail($this->page['id']);
            $page->delete();

            Flux::toast(text: 'Página desactivada con éxito.', variant: 'success');
            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Error al desactivar la página.', variant: 'danger');
        }
    }

    /**
     * Carga una página y abre la confirmación de eliminación permanente.
     *
     * @param  int $id
     * @return void
     */
    // Carga una página y abre la confirmación de eliminación permanente.
    //   Ejemplo: Desde la vista: wire:click="deleteItem({{ $row->id }})"
    //   Resultado: El diálogo queda abierto para confirmar la eliminación permanente.
    public function deleteItem(int $id): void
    {
        $this->page = Page::withTrashed()->findOrFail($id)->toArray();
        Flux::modal('deleteItem')->show();
    }

    /**
     * Elimina permanentemente la página confirmada y notifica el resultado.
     *
     * @return void
     */
    // Elimina permanentemente la página confirmada y notifica el resultado.
    //   Ejemplo: Desde la vista: wire:click="destroy"
    //   Resultado: El registro se elimina y la interfaz informa el resultado.
    public function destroy(): void
    {
        try {
            $page = Page::withTrashed()->findOrFail($this->page['id']);
            $page->forceDelete();

            Flux::toast(text: 'Página eliminada con éxito.', variant: 'success');
            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Error al eliminar la página.', variant: 'danger');
        }
    }

    /**
     * Restablece los datos y errores del formulario y cierra los modales.
     *
     * @return void
     */
    // Restablece los datos y errores del formulario y cierra los modales.
    //   Ejemplo: Desde la vista: wire:click="resetData"
    //   Resultado: Los datos y errores del formulario se limpian y se cierran los modales.
    public function resetData(): void
    {
        $this->reset('page');
        $this->resetValidation();
        Flux::modals()->close();
    }

    /**
     * Devuelve los tipos configurados en Page para llenar el selector del formulario.
     *
     * @return array
     */
    // Devuelve los tipos configurados en Page para llenar el selector del formulario.
    //   Ejemplo: Blade itera `@foreach ($this->typePages() as $type)`.
    //   Resultado: El selector presenta los tipos admitidos para una página.
    public function typePages(): array
    {
        return Page::type;
    }
}
