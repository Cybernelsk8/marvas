<?php

namespace App\Livewire\Admin\Clinical;

use App\Models\Sucursal;
use App\Traits\DataTable;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Sedes')]
class Branches extends Component
{
    use DataTable;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'align' => 'center'],
        ['index' => 'nombre', 'label' => 'Nombre'],
        ['index' => 'direccion', 'label' => 'Dirección'],
        ['index' => 'telefono', 'label' => 'Teléfono'],
        ['index' => 'activa', 'label' => 'Estado', 'exclude' => true],
        ['index' => 'actions', 'label' => '', 'width' => '100px'],
    ];

    public ?int $editingId = null;

    public string $nombre = '';

    public string $direccion = '';

    public string $telefono = '';

    public bool $activa = true;

    public function mount(): void
    {
        $this->authorize('clinical-catalogs.manage');
        $this->sortBy = 'nombre';
        $this->sortDirection = 'asc';
    }

    #[Computed]
    public function rows()
    {
        return Sucursal::query()
            ->filterAdvance($this->headers, [
                'search' => $this->search,
                'sort' => ['field' => $this->sortBy, 'direction' => $this->sortDirection],
                'filters' => $this->processFilters(),
            ])
            ->paginate($this->per_page ?? 10);
    }

    public function save(): void
    {
        $this->authorize('clinical-catalogs.manage');
        $data = $this->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:10000'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'activa' => ['required', 'boolean'],
        ]);

        $branch = $this->editingId
            ? Sucursal::query()->findOrFail($this->editingId)
            : new Sucursal;
        $branch->fill([
            ...$data,
            'nombre' => trim($data['nombre']),
            'direccion' => filled($data['direccion']) ? trim($data['direccion']) : null,
            'telefono' => filled($data['telefono']) ? trim($data['telefono']) : null,
        ])->save();

        Flux::modal('branch-form')->close();
        Flux::toast(text: $this->editingId ? 'Sede actualizada.' : 'Sede creada.', variant: 'success');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorize('clinical-catalogs.manage');
        $branch = Sucursal::query()->findOrFail($id);
        $this->editingId = $branch->id;
        $this->nombre = $branch->nombre;
        $this->direccion = $branch->direccion ?? '';
        $this->telefono = $branch->telefono ?? '';
        $this->activa = $branch->activa;
        Flux::modal('branch-form')->show();
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('clinical-catalogs.manage');
        $branch = Sucursal::query()->findOrFail($id);
        $branch->update(['activa' => ! $branch->activa]);
        Flux::toast(text: $branch->activa ? 'Sede activada.' : 'Sede desactivada.', variant: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'nombre', 'direccion', 'telefono', 'activa']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.clinical.branches');
    }
}
