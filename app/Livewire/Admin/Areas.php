<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Area;
use App\Traits\DataTable;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Attributes\Title;

/** Componente Livewire que lista y administra áreas y dependencias. */
#[Title('Áreas')]
class Areas extends Component
{
    use DataTable;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'align' => 'center'],
        ['index' => 'name', 'label' => 'Área'],
        ['index' => 'dependency.name', 'label' => 'Pertenece a'],
        ['index' => 'deleted_at', 'label' => 'Estado'],
        ['index' => 'actions', 'label' => '', 'width' => '100px'],
    ];

    public array $area = [
        'name' => null,
        'area_id' => null,
        'deleted_at' => null,
    ];

    /**
     * Lista áreas incluidas en la papelera aplicando búsqueda, orden y filtros para alimentar la tabla paginada.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    // Lista áreas incluidas en la papelera aplicando búsqueda, orden y filtros para alimentar la tabla paginada.
    //   Ejemplo: Desde Blade: {{ $this->rows->total() }}.
    //   Resultado: La tabla recibe la página filtrada y conserva el total para la paginación.
    #[Computed]
    public function rows()
    {
        $query = Area::with(['dependency'])
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
     * Entrega la vista y carga las áreas que pueden elegirse como dependencia.
     *
     * @return \Illuminate\Contracts\View\View
     */
    // Entrega la vista y carga las áreas que pueden elegirse como dependencia.
    //   Ejemplo: Livewire lo invoca al mostrar o actualizar el componente.
    //   Resultado: Livewire entrega la vista con los datos preparados para sus controles.
    public function render()
    {
        $dependencies = Area::query()
            ->withTrashed()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.areas', compact('dependencies'));
    }

    /**
     * Valida y crea un área; al terminar limpia el formulario y notifica el resultado.
     *
     * @return void
     */
    // Valida y crea un área; al terminar limpia el formulario y notifica el resultado.
    //   Ejemplo: Desde la vista: wire:click="store"
    //   Resultado: El registro se crea y la interfaz confirma el resultado.
    public function store(): void
    {
        $this->validate([
            'area.name' => 'required|string|max:255|unique:areas,name',
            'area.area_id' => 'nullable|exists:areas,id',
        ]);

        try {
            Area::create([
                'name' => trim($this->area['name']),
                'area_id' => $this->area['area_id'] ?? null,
            ]);

            Flux::toast(text: 'Área creada exitosamente.', variant: 'success');
            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Ocurrió un error al crear el área.', variant: 'danger');
        }
    }

    /**
     * Carga el área seleccionada, incluso si está desactivada, y abre el formulario de edición.
     *
     * @param  int $id
     * @return void
     */
    // Carga el área seleccionada, incluso si está desactivada, y abre el formulario de edición.
    //   Ejemplo: Desde la vista: wire:click="edit({{ $row->id }})"
    //   Resultado: El formulario se abre con los datos del registro seleccionado.
    public function edit(int $id): void
    {
        $this->area = Area::withTrashed()->findOrFail($id)->toArray();
        Flux::modal('editArea')->show();
    }

    /**
     * Valida y guarda los cambios del área seleccionada y limpia el formulario.
     *
     * @return void
     */
    // Valida y guarda los cambios del área seleccionada y limpia el formulario.
    //   Ejemplo: Desde la vista: wire:click="update"
    //   Resultado: Los cambios válidos quedan guardados y la interfaz confirma el resultado.
    public function update(): void
    {
        $this->validate([
            'area.name' => 'required|string|max:255|unique:areas,name,' . ($this->area['id'] ?? 0),
            'area.area_id' => 'nullable|exists:areas,id',
        ]);

        try {
            $area = Area::withTrashed()->findOrFail($this->area['id']);
            $newName = trim($this->area['name']);
            $newParentId = $this->area['area_id'] ?? null;

            $hasChanges = $area->name !== $newName || $area->area_id !== $newParentId;

            if ($hasChanges) {
                $area->fill([
                    'name' => $newName,
                    'area_id' => $newParentId,
                ]);
                $area->save();
                Flux::toast(text: 'Área actualizada exitosamente.', variant: 'success');
            }

            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Ocurrió un error al actualizar el área.', variant: 'danger');
        }
    }

    /**
     * Carga el área elegida y abre el diálogo para confirmar el cambio de estado.
     *
     * @param  int $id
     * @return void
     */
    // Carga el área elegida y abre el diálogo para confirmar el cambio de estado.
    //   Ejemplo: Desde la vista: wire:click="disableItem({{ $row->id }})"
    //   Resultado: El diálogo queda abierto para confirmar el cambio de estado.
    public function disableItem(int $id): void
    {
        $this->area = Area::withTrashed()->findOrFail($id)->toArray();
        Flux::modal('disableArea')->show();
    }

    /**
     * Alterna entre borrado lógico y restauración según el estado actual del área.
     *
     * @return void
     */
    // Alterna entre borrado lógico y restauración según el estado actual del área.
    //   Ejemplo: Desde la vista: wire:click="disabled"
    //   Resultado: El registro se desactiva o restaura y la interfaz confirma el cambio.
    public function disabled(): void
    {
        try {
            $area = Area::withTrashed()->findOrFail($this->area['id']);

            if ($area->trashed()) {
                $area->restore();
                Flux::toast(text: 'Área habilitada exitosamente.', variant: 'success');
            } else {
                $area->delete();
                Flux::toast(text: 'Área deshabilitada exitosamente.', variant: 'success');
            }

            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Ocurrió un error al cambiar el estado del área.', variant: 'danger');
        }
    }

    /**
     * Carga el área seleccionada y abre la confirmación de eliminación física.
     *
     * @param  int $id
     * @return void
     */
    // Carga el área seleccionada y abre la confirmación de eliminación física.
    //   Ejemplo: Desde la vista: wire:click="delete({{ $row->id }})"
    //   Resultado: El diálogo identifica el registro pendiente de eliminación.
    public function delete(int $id): void
    {
        $this->area = Area::withTrashed()->findOrFail($id)->toArray();
        Flux::modal('deleteArea')->show();
    }

    /**
     * Elimina definitivamente el área confirmada y comunica el resultado.
     *
     * @return void
     */
    // Elimina definitivamente el área confirmada y comunica el resultado.
    //   Ejemplo: Desde la vista: wire:click="destroy"
    //   Resultado: El registro se elimina y la interfaz informa el resultado.
    public function destroy(): void
    {
        try {
            $area = Area::withTrashed()->findOrFail($this->area['id']);
            $area->forceDelete();

            Flux::toast(text: 'Área eliminada exitosamente.', variant: 'success');
            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Ocurrió un error al eliminar el área.', variant: 'danger');
        }
    }

    /**
     * Restablece los campos y errores del formulario y cierra los modales.
     *
     * @return void
     */
    // Restablece los campos y errores del formulario y cierra los modales.
    //   Ejemplo: Desde la vista: wire:click="resetData"
    //   Resultado: Los datos y errores del formulario se limpian y se cierran los modales.
    public function resetData(): void
    {
        $this->reset('area');
        $this->resetValidation();
        Flux::modals()->close();
    }
}
