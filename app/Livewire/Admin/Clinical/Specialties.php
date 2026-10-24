<?php

namespace App\Livewire\Admin\Clinical;

use App\Models\Especialidad;
use App\Traits\DataTable;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Especialidades')]
class Specialties extends Component
{
    use DataTable;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'align' => 'center'],
        ['index' => 'nombre', 'label' => 'Nombre'],
        ['index' => 'slug', 'label' => 'Slug'],
        ['index' => 'activa', 'label' => 'Estado', 'exclude' => true],
        ['index' => 'actions', 'label' => '', 'width' => '100px'],
    ];

    public ?int $editingId = null;

    public string $nombre = '';

    public string $slug = '';

    public string $descripcion = '';

    public string $color_hex = '';

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
        return Especialidad::query()
            ->filterAdvance($this->headers, [
                'search' => $this->search,
                'sort' => ['field' => $this->sortBy, 'direction' => $this->sortDirection],
                'filters' => $this->processFilters(),
            ])
            ->paginate($this->per_page ?? 10);
    }

    public function updatedNombre(string $nombre): void
    {
        if (! $this->editingId) {
            $this->slug = Str::slug($nombre);
        }
    }

    public function save(): void
    {
        $this->authorize('clinical-catalogs.manage');
        $this->slug = Str::slug($this->slug ?: $this->nombre);
        $data = $this->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('especialidades', 'slug')->ignore($this->editingId),
            ],
            'descripcion' => ['nullable', 'string', 'max:10000'],
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'activa' => ['required', 'boolean'],
        ]);

        $specialty = $this->editingId
            ? Especialidad::query()->findOrFail($this->editingId)
            : new Especialidad;
        $specialty->fill([
            ...$data,
            'nombre' => trim($data['nombre']),
            'slug' => Str::slug($data['slug']),
            'descripcion' => filled($data['descripcion']) ? trim($data['descripcion']) : null,
            'color_hex' => $data['color_hex'] ?: null,
        ])->save();

        Flux::modal('specialty-form')->close();
        Flux::toast(text: $this->editingId ? 'Especialidad actualizada.' : 'Especialidad creada.', variant: 'success');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorize('clinical-catalogs.manage');
        $specialty = Especialidad::query()->findOrFail($id);
        $this->editingId = $specialty->id;
        $this->nombre = $specialty->nombre;
        $this->slug = $specialty->slug;
        $this->descripcion = $specialty->descripcion ?? '';
        $this->color_hex = $specialty->color_hex ?? '';
        $this->activa = $specialty->activa;
        Flux::modal('specialty-form')->show();
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('clinical-catalogs.manage');
        $specialty = Especialidad::query()->findOrFail($id);
        $specialty->update(['activa' => ! $specialty->activa]);
        Flux::toast(text: $specialty->activa ? 'Especialidad activada.' : 'Especialidad desactivada.', variant: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'nombre', 'slug', 'descripcion', 'color_hex', 'activa']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.clinical.specialties');
    }
}
