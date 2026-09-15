<?php

namespace App\Livewire;

use App\Models\Cita;
use App\Traits\DataTable;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Attributes\Title;

#[Title('Solicitudes')]
class Solicitud extends Component
{

    use DataTable;

    public array $headers = [
        ['index' => 'expediente.id', 'label' => 'Expediente #', 'type' => 'numeric'],
        ['index' => 'expediente.paciente.nombre_completo', 'label' => 'Paciente'],
        ['index' => 'fecha_hora_inicio', 'label' => 'Fecha y Hora', 'type' => 'date'],
        ['index' => 'estado', 'label' => 'Estado'],
        ['index' => 'tipo_cita', 'label' => 'Tipo Cita'],
    ];

    public array $solicitud = [];


    #[Computed]
    public function rows()
    {
        $query = Cita::with(['expediente.paciente'])
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
        return view('livewire.solicitud');
    }

    public function showSolicitud(int $id)
    {
        $solicitud = Cita::with(['expediente.paciente'])->findOrFail($id);
        $this->solicitud = $solicitud->toArray();
        Flux::modal('solicitud-modal')->show();
    }

    public function resetData()
    {
        $this->reset(['solicitud']);
        Flux::modals()->close();
    }
}
