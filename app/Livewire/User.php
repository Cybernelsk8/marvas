<?php

namespace App\Livewire;

use App\Traits\DataTable;
use Livewire\Attributes\Computed;
use App\Models\User as UserModel;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Presenta datos de usuario y eventos seleccionados en el calendario.
 */
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

    /**
     * Calcula la página de usuarios con la búsqueda, filtros y orden actuales.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Calcula la página de usuarios con la búsqueda, filtros y orden actuales.
     */
    // Calcula la página de usuarios con la búsqueda, filtros y orden actuales.
    //   Desde la vista: @foreach ($this->rows as $user) ... @endforeach
    //   Resultado: filas paginadas de usuarios.
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

    /**
     * Prepara las opciones del selector y establece hoy como fecha activa del calendario.
     * @return \Illuminate\Contracts\View\View Prepara las opciones del selector y establece hoy como fecha activa del calendario.
     */
    // Prepara las opciones del selector y establece hoy como fecha activa del calendario.
    //   Ejemplo: al abrir la pantalla se obtienen usuarios para las opciones.
    //   Resultado: la vista recibe opciones y fecha activa actual.
    public function render()
    {
        $this->options = UserModel::all()->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name,
        ])->toArray();

        $this->diaActivo = now()->format('Y-m-d');

        return view('livewire.user');
    }

    /**
     * Recibe el evento del calendario para actualizar el día y abrir su panel de agenda.
     * @param string $date Dato recibido por el componente.
     * @param array $events Dato recibido por el componente.
     * @return void Recibe el evento del calendario para actualizar el día y abrir su panel de agenda.
     */
    // Recibe el evento del calendario para actualizar el día y abrir su panel de agenda.
    //   Emisión: $dispatch("calendar-day-selected", date: "2026-08-03", events: $eventos)
    //   Resultado: el modal muestra los eventos del día seleccionado.
    #[On('calendar-day-selected')]
    public function onDiaSeleccionado(string $date, array $events): void
    {
        $this->diaActivo = $date;
        $this->eventosDelDia = $events;
        Flux::modal('mi-modal-agenda')->show(); // tu propio flux:modal, a tu gusto
    }
}
