<?php

namespace App\Livewire;

use App\Traits\DataTable;
use Livewire\Attributes\Computed;
use App\Models\User as UserModel;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class User extends Component
{
    use DataTable;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'align' => 'center', 'type' => 'numeric'],
        ['index' => 'name', 'label' => 'Usuario', 'class' => 'uppercase'],
        ['index' => 'email', 'label' => 'Correo'],
        ['index' => 'actions', 'label' => ''],
    ];

    public ?array $selected = null;
    public ?array $options = null;
    public Carbon $birthdate;
    public ?array $range = ['start' => null, 'end' => null];
    public ?array $holidays = [];
    public ?string $fecha = null;
    public array $eventos = [
        '2026-08-03' => [
            ['title' => 'Reunión con cliente1', 'time' => '10:00', 'color' => '#3b82f6'],
            ['title' => 'Reunión con cliente2', 'time' => '10:00', 'color' => '#3b82f6'],
            ['title' => 'Reunión con cliente3', 'time' => '10:00', 'color' => '#3b82f6'],
            ['title' => 'Reunión con cliente4', 'time' => '10:00', 'color' => '#3b82f6'],
            ['title' => 'Reunión con cliente5', 'time' => '10:00', 'color' => '#3b82f6'],
        ],

        '2026-07-31' => [
            ['title' => 'Reunión con cliente1', 'time' => '10:00', 'color' => '#3b82f6'],
            ['title' => 'Reunión con cliente2', 'time' => '10:00', 'color' => '#3b82f6'],
            ['title' => 'Reunión con cliente3', 'time' => '10:00', 'color' => '#3b82f6'],
            ['title' => 'Reunión con cliente4', 'time' => '10:00', 'color' => '#3b82f6'],
            ['title' => 'Reunión con cliente5', 'time' => '10:00', 'color' => '#3b82f6'],
        ],
    ];

    public ?string $diaActivo = null;
    public array $eventosDelDia = [];

    #[Computed]
    public function rows()
    {
        $query = UserModel::filterAdvance($this->headers, [
            'search' => $this->search,
            'sort' => [
                'field' => $this->sortBy,
                'direction' => $this->sortDirection
            ],
            'filters' => $this->processFilters(),
            'select_only' => true,
            'select_extra' => []
        ]);
        return $query->paginate($this->per_page);
    }


    public function render()
    {
        $this->options = UserModel::all()->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name,
        ])->toArray();

        $this->diaActivo = now()->format('Y-m-d');

        return view('livewire.user');
    }

    #[On('calendar-day-selected')]
    public function onDiaSeleccionado(string $date, array $events): void
    {
        $this->diaActivo = $date;
        $this->eventosDelDia = $events;
        Flux::modal('mi-modal-agenda')->show(); // tu propio flux:modal, a tu gusto
    }
}
