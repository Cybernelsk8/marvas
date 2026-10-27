<?php

namespace App\Livewire\Admin\Clinical;

use App\Models\RegionCorporal;
use App\Traits\DataTable;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Componente Livewire para administrar las regiones del mapa corporal y trazar su contorno sobre la imagen. */
#[Title('Regiones corporales')]
class BodyRegions extends Component
{
    use DataTable;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'align' => 'center'],
        ['index' => 'nombre', 'label' => 'Nombre'],
        ['index' => 'codigo', 'label' => 'Código'],
        ['index' => 'vista', 'label' => 'Vista'],
        ['index' => 'trazado', 'label' => 'Contorno', 'exclude' => true],
        ['index' => 'activo', 'label' => 'Estado', 'exclude' => true],
        ['index' => 'actions', 'label' => '', 'width' => '100px'],
    ];

    public ?int $editingId = null;

    public string $codigo = '';

    public string $nombre = '';

    public string $vista = 'anterior';

    public bool $tiene_lateralidad = true;

    public int $orden = 0;

    public bool $activo = true;

    /** Contornos SVG por lateralidad: ['derecha' => 'M.. Z', 'izquierda' => 'M.. Z', 'central' => 'M.. Z']. */
    public array $trazos = [];

    /**
     * Autoriza el catálogo y define el orden inicial por vista y orden.
     */
    // Autoriza el catálogo y define el orden inicial por vista y orden.
    //   Ejemplo: Livewire invoca este hook al montar el componente.
    //   Resultado: El componente queda autorizado y configurado con sus valores iniciales.
    public function mount(): void
    {
        $this->authorize('clinical-catalogs.manage');
        $this->sortBy = 'vista';
        $this->sortDirection = 'asc';
    }

    /**
     * Consulta regiones aplicando búsqueda, orden, filtros y paginación.
     *
     * @return LengthAwarePaginator
     */
    // Consulta regiones aplicando búsqueda, orden, filtros y paginación.
    //   Ejemplo: Desde Blade: {{ $this->rows->total() }}.
    //   Resultado: La tabla recibe la página filtrada y conserva el total para la paginación.
    #[Computed]
    public function rows()
    {
        return RegionCorporal::query()
            ->filterAdvance($this->headers, [
                'search' => $this->search,
                'sort' => ['field' => $this->sortBy, 'direction' => $this->sortDirection],
                'filters' => $this->processFilters(),
            ])
            ->paginate($this->per_page ?? 10);
    }

    /**
     * Contornos de las demás regiones de la misma vista, mostrados como referencia al trazar.
     *
     * @return array<int, array{codigo: string, paths: array<int, string>}>
     */
    // Contornos de las demás regiones de la misma vista, mostrados como referencia al trazar.
    //   Ejemplo: Desde Blade: @foreach ($this->referencias as $ref) ... @endforeach
    //   Resultado: Lista de regiones con sus paths para dibujarlas en gris tenue.
    #[Computed]
    public function referencias(): array
    {
        return RegionCorporal::query()
            ->where('vista', $this->vista)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->get()
            ->map(fn (RegionCorporal $r) => ['codigo' => $r->codigo, 'paths' => array_values($r->trazos ?? [])])
            ->all();
    }

    /**
     * Hook Livewire que genera el código al escribir el nombre al crear, sin pisarlo durante edición.
     */
    // Hook Livewire que genera el código al escribir el nombre al crear, sin pisarlo durante edición.
    //   Ejemplo: Livewire lo invoca al actualizar la propiedad asociada a updatedNombre.
    //   Resultado: El código queda como slug en snake_case (ej. "Serrato mayor" -> serrato_mayor).
    public function updatedNombre(string $nombre): void
    {
        if (! $this->editingId) {
            $this->codigo = Str::slug($nombre, '_');
        }
    }

    /**
     * Valida y crea o actualiza una región con sus contornos.
     */
    // Valida y crea o actualiza una región con sus contornos.
    //   Ejemplo: Desde la vista: wire:click="save"
    //   Resultado: La región se guarda; el mapa de dolor la usa de inmediato.
    public function save(): void
    {
        $this->authorize('clinical-catalogs.manage');
        $this->codigo = Str::slug($this->codigo ?: $this->nombre, '_');

        $data = $this->validate([
            'codigo' => ['required', 'string', 'max:40', Rule::unique('regiones_corporales', 'codigo')->ignore($this->editingId)],
            'nombre' => ['required', 'string', 'max:80'],
            'vista' => ['required', Rule::in(['anterior', 'posterior'])],
            'tiene_lateralidad' => ['boolean'],
            'orden' => ['required', 'integer', 'min:0', 'max:65535'],
            'activo' => ['boolean'],
            'trazos' => ['array'],
            'trazos.*' => ['nullable', 'string', 'max:5000', 'regex:/^M[\d\s.LZ-]+$/'],
        ]);

        $lados = $data['tiene_lateralidad'] ? ['derecha', 'izquierda'] : ['central'];
        $trazos = collect($data['trazos'] ?? [])
            ->only($lados)
            ->filter()
            ->all();

        $region = $this->editingId ? RegionCorporal::query()->findOrFail($this->editingId) : new RegionCorporal;
        $region->fill([
            'codigo' => $data['codigo'],
            'nombre' => trim($data['nombre']),
            'vista' => $data['vista'],
            'tiene_lateralidad' => $data['tiene_lateralidad'],
            'orden' => $data['orden'],
            'activo' => $data['activo'],
            'trazos' => $trazos ?: null,
        ])->save();

        Flux::modal('body-region-form')->close();
        Flux::toast(text: $this->editingId ? 'Región actualizada.' : 'Región creada.', variant: 'success');
        $this->resetForm();
    }

    /**
     * Carga la región y abre el formulario con el trazador en modo edición.
     */
    // Carga la región y abre el formulario con el trazador en modo edición.
    //   Ejemplo: Desde la vista: wire:click="edit({{ $row->id }})"
    //   Resultado: El modal muestra los datos y el contorno actual para corregirlo.
    public function edit(int $id): void
    {
        $this->authorize('clinical-catalogs.manage');
        $region = RegionCorporal::query()->findOrFail($id);
        $this->editingId = $region->id;
        $this->codigo = $region->codigo;
        $this->nombre = $region->nombre;
        $this->vista = $region->vista;
        $this->tiene_lateralidad = $region->tiene_lateralidad;
        $this->orden = $region->orden;
        $this->activo = $region->activo;
        $this->trazos = $region->trazos ?? [];
        Flux::modal('body-region-form')->show();
    }

    /**
     * Alterna el estado de la región sin eliminarla (el historial de dolor se conserva).
     */
    // Alterna el estado de la región sin eliminarla (el historial de dolor se conserva).
    //   Ejemplo: Desde la vista: wire:click="toggleActive({{ $row->id }})"
    //   Resultado: La región deja de aparecer en el mapa pero sus registros históricos permanecen.
    public function toggleActive(int $id): void
    {
        $this->authorize('clinical-catalogs.manage');
        $region = RegionCorporal::query()->findOrFail($id);
        $region->update(['activo' => ! $region->activo]);
        Flux::toast(text: $region->activo ? 'Región activada.' : 'Región desactivada.', variant: 'success');
    }

    /**
     * Restablece los campos y errores de validación del formulario.
     */
    // Restablece los campos y errores de validación del formulario.
    //   Ejemplo: Desde la vista: wire:click="resetForm"
    //   Resultado: Los campos regresan a sus valores iniciales y se limpian los errores.
    public function resetForm(): void
    {
        $this->reset(['editingId', 'codigo', 'nombre', 'vista', 'tiene_lateralidad', 'orden', 'activo', 'trazos']);
        $this->resetValidation();
    }

    /**
     * Devuelve la vista administrativa de regiones corporales.
     *
     * @return View
     */
    // Devuelve la vista administrativa de regiones corporales.
    //   Ejemplo: Livewire lo invoca al mostrar o actualizar el componente.
    //   Resultado: Livewire entrega la vista con los datos preparados para sus controles.
    public function render()
    {
        return view('livewire.admin.clinical.body-regions');
    }
}
