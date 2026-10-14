<?php

namespace App\Livewire\Admin\Patient;

use App\Models\Paciente;
use App\Traits\DataTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pacientes')]
class Index extends Component
{
    use DataTable;

    public string $status = 'active';

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'type' => 'numeric'],
        ['index' => 'expediente.id', 'label' => 'Expediente #', 'type' => 'numeric'],
        ['index' => 'nombre_completo', 'label' => 'Paciente'],
        ['index' => 'numero_dpi', 'label' => 'DPI'],
        ['index' => 'telefono', 'label' => 'Teléfono', 'type' => 'phone'],
        ['index' => 'email', 'label' => 'Correo'],
        ['index' => 'activo', 'label' => 'Estado', 'type' => 'boolean'],
        ['index' => 'actions', 'label' => ''],
    ];

    public function mount(): void
    {
        $this->authorize('page.view.patients');
        $this->sortBy = 'nombre_completo';
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function rows()
    {
        $query = Paciente::query()
            ->when($this->status !== 'all', function (Builder $query): void {
                $query->where('activo', $this->status === 'active');
            })
            ->filterAdvance($this->headers, [
                'sort' => [
                    'field' => $this->sortBy,
                    'direction' => $this->sortDirection,
                ],
                'filters' => $this->processFilters(),
            ]);

        if (trim($this->search) !== '') {
            $digits = preg_replace('/\D+/', '', $this->search);

            $query->where(function (Builder $query) use ($digits): void {
                $query->filterAdvance($this->headers, ['search' => $this->search]);

                if ($digits !== '') {
                    $query->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(COALESCE(pacientes.telefono, ''), '-', ''), ' ', ''), '+', '') LIKE ?",
                        ["%{$digits}%"],
                    )->orWhereRaw(
                        "REPLACE(REPLACE(COALESCE(pacientes.numero_dpi, ''), '-', ''), ' ', '') LIKE ?",
                        ["%{$digits}%"],
                    );
                }
            });
        }

        return $query->paginate($this->per_page);
    }

    public function render()
    {
        return view('livewire.admin.patient.index');
    }
}
