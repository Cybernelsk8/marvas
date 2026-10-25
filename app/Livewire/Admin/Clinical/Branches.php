<?php

namespace App\Livewire\Admin\Clinical;

use App\Models\Sucursal;
use App\Traits\DataTable;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Componente Livewire para administrar el catálogo de sedes clínicas. */
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

    /**
     * Autoriza el acceso al catálogo y define el orden inicial del listado.
     *
     * @return void
     */
    // Autoriza el acceso al catálogo y define el orden inicial del listado.
    //   Ejemplo: Livewire invoca este hook al montar el componente.
    //   Resultado: El componente queda autorizado y configurado con sus valores iniciales.
    public function mount(): void
    {
        $this->authorize('clinical-catalogs.manage');
        $this->sortBy = 'nombre';
        $this->sortDirection = 'asc';
    }

    /**
     * Consulta sedes aplicando búsqueda, orden, filtros y paginación.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    // Consulta sedes aplicando búsqueda, orden, filtros y paginación.
    //   Ejemplo: Desde Blade: {{ $this->rows->total() }}.
    //   Resultado: La tabla recibe la página filtrada y conserva el total para la paginación.
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

    /**
     * Valida y crea o actualiza una sede, normaliza campos opcionales y cierra el formulario.
     *
     * @return void
     */
    // Valida y crea o actualiza una sede, normaliza campos opcionales y cierra el formulario.
    //   Ejemplo: Desde la vista: wire:click="save"
    //   Resultado: Los datos se guardan y la interfaz confirma creación o actualización.
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

    /**
     * Carga una sede y abre el formulario con sus datos actuales.
     *
     * @param  int $id
     * @return void
     */
    // Carga una sede y abre el formulario con sus datos actuales.
    //   Ejemplo: Desde la vista: wire:click="edit({{ $row->id }})"
    //   Resultado: El formulario se abre con los datos del registro seleccionado.
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

    /**
     * Alterna el indicador activa; no hay borrado físico porque las citas dependen de la sede.
     *
     * @param  int $id
     * @return void
     */
    // Alterna el indicador activa; no hay borrado físico porque las citas dependen de la sede.
    //   Ejemplo: Desde la vista: wire:click="toggleActive({{ $row->id }})"
    //   Resultado: El estado se alterna sin borrar el registro y la interfaz lo confirma.
    public function toggleActive(int $id): void
    {
        $this->authorize('clinical-catalogs.manage');
        $branch = Sucursal::query()->findOrFail($id);
        $branch->update(['activa' => ! $branch->activa]);
        Flux::toast(text: $branch->activa ? 'Sede activada.' : 'Sede desactivada.', variant: 'success');
    }

    /**
     * Restablece los campos del formulario y limpia errores de validación.
     *
     * @return void
     */
    // Restablece los campos del formulario y limpia errores de validación.
    //   Ejemplo: Desde la vista: wire:click="resetForm"
    //   Resultado: Los campos regresan a sus valores iniciales y se limpian los errores.
    public function resetForm(): void
    {
        $this->reset(['editingId', 'nombre', 'direccion', 'telefono', 'activa']);
        $this->resetValidation();
    }

    /**
     * Devuelve la vista de administración del catálogo de sedes.
     *
     * @return \Illuminate\Contracts\View\View
     */
    // Devuelve la vista de administración del catálogo de sedes.
    //   Ejemplo: Livewire lo invoca al mostrar o actualizar el componente.
    //   Resultado: Livewire entrega la vista con los datos preparados para sus controles.
    public function render()
    {
        return view('livewire.admin.clinical.branches');
    }
}
