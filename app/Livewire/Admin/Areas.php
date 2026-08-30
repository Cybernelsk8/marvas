<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Area;
use App\Traits\DataTable;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

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

    public function render()
    {
        $dependencies = Area::query()
            ->withTrashed()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.areas', compact('dependencies'));
    }

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

    public function edit(int $id): void
    {
        $this->area = Area::withTrashed()->findOrFail($id)->toArray();
        Flux::modal('editArea')->show();
    }

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

    public function disableItem(int $id): void
    {
        $this->area = Area::withTrashed()->findOrFail($id)->toArray();
        Flux::modal('disableArea')->show();
    }

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

    public function delete(int $id): void
    {
        $this->area = Area::withTrashed()->findOrFail($id)->toArray();
        Flux::modal('deleteArea')->show();
    }

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

    public function resetData(): void
    {
        $this->reset('area');
        $this->resetValidation();
        Flux::modals()->close();
    }
}
