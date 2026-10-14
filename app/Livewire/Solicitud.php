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
        ['index' => 'fecha_hora_inicio', 'label' => 'Fecha y Hora'],
        ['index' => 'estado', 'label' => 'Estado'],
        ['index' => 'tipo_cita', 'label' => 'Tipo Cita'],
    ];

    public array $filtersOptions = [];

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

    public function mount()
    {
        $this->sortBy = 'fecha_hora_inicio';
        $this->filters = [['field' => 'estado', 'operator' => '=', 'value' => 'solicitada']];

        $this->filtersOptions = [
            [
                'label' => 'Ordenar por',
                'icon' => 'bars-arrow-down',
                'type' => 'sort',
                'options' => [
                    ['value' => ['field' => 'fecha_hora_inicio', 'direction' => 'desc'], 'label' => 'Más recientes primero'],
                    ['value' => ['field' => 'fecha_hora_inicio', 'direction' => 'asc'], 'label' => 'Más antiguas primero'],
                    ['value' => ['field' => 'expediente.paciente.nombres', 'direction' => 'asc'], 'label' => 'Nombre (A-Z)'],
                    ['value' => ['field' => 'expediente.paciente.nombres', 'direction' => 'desc'], 'label' => 'Nombre (Z-A)'],
                    ['value' => null, 'label' => 'Restaurar'],
                ],
            ],
            [
                'label' => 'Fecha registro',
                'icon' => 'calendar',
                'type' => 'filter',
                'options' => [
                    ['value' => ['field' => 'created_at', 'operator' => 'like', 'value' => now()->subDay()->format('Y-m-d')], 'label' => 'Ayer'],
                    ['value' => ['field' => 'created_at', 'operator' => 'like', 'value' => now()->format('Y-m-d')], 'label' => 'Hoy'],
                    ['value' => ['field' => 'created_at', 'operator' => 'like', 'value' => now()->addDay()->format('Y-m-d')], 'label' => 'Mañana'],
                    ['value' => null, 'label' => 'Todos'],
                ],
            ],
            [
                'label' => 'Por estado',
                'icon' => 'flag',
                'type' => 'filter',
                'options' => [
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'solicitada'], 'label' => 'Solicitada'],
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'agendada'], 'label' => 'Agendada'],
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'confirmada'], 'label' => 'Confirmada'],
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'en_atencion'], 'label' => 'En atención'],
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'finalizada'], 'label' => 'Finalizada'],
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'cancelada'], 'label' => 'Cancelada'],
                    ['value' => ['field' => 'estado', 'operator' => '=', 'value' => 'reprogramada'], 'label' => 'Reprogramada'],
                    ['value' => null, 'label' => 'Todos'],
                ],
            ],
            [
                'label' => 'Por tipo',
                'icon' => 'tag',
                'type' => 'filter',
                'options' => [
                    ['value' => ['field' => 'tipo_cita', 'operator' => '=', 'value' => 'primera vez'], 'label' => 'Primera vez'],
                    ['value' => ['field' => 'tipo_cita', 'operator' => '=', 'value' => 'seguimiento'], 'label' => 'Seguimiento'],
                    ['value' => null, 'label' => 'Todos'],
                ],
            ],
        ];
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

    public function store() {}

    public function resetData()
    {
        $this->reset(['solicitud']);
        Flux::modals()->close();
    }
}
