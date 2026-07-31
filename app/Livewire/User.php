<?php

namespace App\Livewire;

use App\Traits\DataTable;
use Livewire\Attributes\Computed;
use App\Models\User as UserModel;
use Flux\Flux;
use Illuminate\Support\Carbon;
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

        return view('livewire.user');
    }
}
