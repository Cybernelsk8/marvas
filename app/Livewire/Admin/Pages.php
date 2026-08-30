<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Page;
use App\Models\Admin\Permission;
use App\Traits\DataTable;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Pages extends Component
{
    use DataTable;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'align' => 'center'],
        ['index' => 'label', 'label' => 'Página'],
        ['index' => 'icon', 'label' => 'Icono'],
        ['index' => 'route', 'label' => 'Ruta'],
        ['index' => 'order', 'label' => 'Orden'],
        ['index' => 'deleted_at', 'label' => 'Estado'],
        ['index' => 'parent.label', 'label' => 'Padre'],
        ['index' => 'type', 'label' => 'Tipo'],
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

    public function render()
    {
        $pages = Page::query()
            ->orderBy('label')
            ->get(['id', 'label']);

        $permissions = Permission::where('name', 'like', '%page.view%')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.pages', compact('pages', 'permissions'));
    }

    public function store(): void
    {
        $this->validate([
            'page.label' => 'required|string|max:255',
            'page.icon' => 'nullable|string|max:255',
            'page.route' => 'nullable|string|max:255',
            'page.order' => 'nullable|integer',
            'page.type' => 'required|in:header,parent,page',
            'page.page_id' => 'required_if:page.type,page|nullable|exists:pages,id',
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

    public function edit(int $id): void
    {
        $this->page = Page::withTrashed()->findOrFail($id)->toArray();
        Flux::modal('editPage')->show();
    }

    public function update(): void
    {
        $this->validate([
            'page.label' => 'required|string|max:255',
            'page.icon' => 'nullable|string|max:255',
            'page.route' => 'nullable|string|max:255',
            'page.order' => 'nullable|integer',
            'page.type' => 'required|in:header,parent,page',
            'page.page_id' => 'required_if:page.type,page|nullable|exists:pages,id',
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
            Flux::toast(text: 'Error al actualizar la página.', variant: 'danger');
        }
    }

    public function disableItem(int $id): void
    {
        $this->page = Page::withTrashed()->findOrFail($id)->toArray();
        Flux::modal('disableItem')->show();
    }

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

    public function deleteItem(int $id): void
    {
        $this->page = Page::withTrashed()->findOrFail($id)->toArray();
        Flux::modal('deleteItem')->show();
    }

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

    public function resetData(): void
    {
        $this->reset('page');
        $this->resetValidation();
        Flux::modals()->close();
    }
}
