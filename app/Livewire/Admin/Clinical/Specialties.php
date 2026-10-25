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

/** Componente Livewire para administrar el catálogo de especialidades clínicas. */
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

    /**
     * Autoriza el catálogo y define el orden inicial por nombre de especialidad.
     *
     * @return void
     */
    // Autoriza el catálogo y define el orden inicial por nombre de especialidad.
    //   Ejemplo: Livewire invoca este hook al montar el componente.
    //   Resultado: El componente queda autorizado y configurado con sus valores iniciales.
    public function mount(): void
    {
        $this->authorize('clinical-catalogs.manage');
        $this->sortBy = 'nombre';
        $this->sortDirection = 'asc';
    }

    /**
     * Consulta especialidades aplicando búsqueda, orden, filtros y paginación.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    // Consulta especialidades aplicando búsqueda, orden, filtros y paginación.
    //   Ejemplo: Desde Blade: {{ $this->rows->total() }}.
    //   Resultado: La tabla recibe la página filtrada y conserva el total para la paginación.
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

    /**
     * Hook Livewire que genera el slug al escribir el nombre al crear, sin pisarlo durante edición.
     *
     * @param  string $nombre
     * @return void
     */
    // Hook Livewire que genera el slug al escribir el nombre al crear, sin pisarlo durante edición.
    //   Ejemplo: Livewire lo invoca al actualizar la propiedad asociada a updatedNombre.
    //   Resultado: Hook Livewire que genera el slug al escribir el nombre al crear, sin pisarlo durante edición.
    public function updatedNombre(string $nombre): void
    {
        if (! $this->editingId) {
            $this->slug = Str::slug($nombre);
        }
    }

    /**
     * Valida y crea o actualiza especialidad con slug y campos opcionales normalizados.
     *
     * @return void
     */
    // Valida y crea o actualiza especialidad con slug y campos opcionales normalizados.
    //   Ejemplo: Desde la vista: wire:click="save"
    //   Resultado: Los datos se guardan y la interfaz confirma creación o actualización.
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

    /**
     * Carga los datos de la especialidad y abre el formulario en modo edición.
     *
     * @param  int $id
     * @return void
     */
    // Carga los datos de la especialidad y abre el formulario en modo edición.
    //   Ejemplo: Desde la vista: wire:click="edit({{ $row->id }})"
    //   Resultado: El formulario se abre con los datos del registro seleccionado.
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

    /**
     * Alterna el estado de la especialidad sin eliminarla físicamente.
     *
     * @param  int $id
     * @return void
     */
    // Alterna el estado de la especialidad sin eliminarla físicamente.
    //   Ejemplo: Desde la vista: wire:click="toggleActive({{ $row->id }})"
    //   Resultado: El estado se alterna sin borrar el registro y la interfaz lo confirma.
    public function toggleActive(int $id): void
    {
        $this->authorize('clinical-catalogs.manage');
        $specialty = Especialidad::query()->findOrFail($id);
        $specialty->update(['activa' => ! $specialty->activa]);
        Flux::toast(text: $specialty->activa ? 'Especialidad activada.' : 'Especialidad desactivada.', variant: 'success');
    }

    /**
     * Restablece los campos y errores de validación del formulario.
     *
     * @return void
     */
    // Restablece los campos y errores de validación del formulario.
    //   Ejemplo: Desde la vista: wire:click="resetForm"
    //   Resultado: Los campos regresan a sus valores iniciales y se limpian los errores.
    public function resetForm(): void
    {
        $this->reset(['editingId', 'nombre', 'slug', 'descripcion', 'color_hex', 'activa']);
        $this->resetValidation();
    }

    /**
     * Devuelve la vista administrativa del catálogo de especialidades.
     *
     * @return \Illuminate\Contracts\View\View
     */
    // Devuelve la vista administrativa del catálogo de especialidades.
    //   Ejemplo: Livewire lo invoca al mostrar o actualizar el componente.
    //   Resultado: Livewire entrega la vista con los datos preparados para sus controles.
    public function render()
    {
        return view('livewire.admin.clinical.specialties');
    }
}
