<?php

namespace App\Livewire\Admin\User;

use App\Models\Admin\Area;
use App\Models\Admin\User;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Traits\DataTable;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use DataTable;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'align' => 'center'],
        ['index' => 'information.nombre_completo', 'label' => 'Usuario'],
        ['index' => 'information.sexo', 'label' => 'Sexo'],
        ['index' => 'information.fecha_nacimiento', 'label' => 'Fecha nacimiento'],
        ['index' => 'area.name', 'label' => 'Area'],
        ['index' => 'role_name', 'label' => 'Role', 'exclude' => true],
        ['index' => 'deleted_at', 'label' => 'Active', 'align' => 'center'],
        ['index' => 'actions', 'label' => ''],
    ];

    public ?int $usuario_id = null;
    public $departamentos, $zonas, $areas, $roles;
    public ?int $departamento_id = 7;
    public ?int $municipio_id = null;

    public array $user = [
        'area_id' => null,
        'role' => null,
        'information' => [
            'nombres' => null,
            'apellidos' => null,
            'cui' => null,
            'telefono' => null,
            'fecha_nacimiento' => null,
            'correo' => null,
            'sexo' => null,
            'domicilio' => [
                'municipio_id' => null,
                'zona_id' => null,
                'colonia' => null,
                'direccion' => null,
            ],
        ],
    ];

    #[Computed]
    public function rows()
    {
        $query = User::with(['information', 'area'])
            ->withTrashed()
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

    public function mount(): void
    {
        $this->departamentos = Departamento::orderBy('nombre')->get();
        $this->areas = Area::orderBy('name')->get();
        $this->roles = Role::orderBy('name')->get();
    }

    public function rules(): array
    {
        return [
            'user.area_id' => 'nullable|exists:areas,id',
            'user.role' => 'nullable|exists:roles,name',
            'user.information.nombres' => 'required|string|max:255',
            'user.information.apellidos' => 'required|string|max:255',
            'user.information.cui' => 'required|digits:13|unique:user_information,cui',
            'user.information.telefono' => 'required|max:9|regex:/^\d{4}-\d{4}$/',
            'user.information.fecha_nacimiento' => 'required|date|date_format:Y-m-d',
            'user.information.correo' => 'required|string|email',
            'user.information.sexo' => 'required|in:M,F',
            'municipio_id' => 'required|exists:municipios,id',
            'user.information.domicilio.municipio_id' => 'required|exists:municipios,id',
            'user.information.domicilio.zona_id' => 'nullable|exists:zonas,id',
            'user.information.domicilio.colonia' => 'nullable|string|max:255',
            'user.information.domicilio.direccion' => 'required|string|max:255',
        ];
    }

    public function render()
    {
        $municipios = Municipio::where('departamento_id', $this->departamento_id)
            ->orderBy('nombre')
            ->get();

        return view('livewire.admin.user.index', compact('municipios'));
    }

    public function store(): void
    {
        $this->syncMunicipioFromForm();
        $this->validate();

        try {
            DB::transaction(function () {
                $user = User::create([
                    'email' => $this->normalizeEmail($this->user['information']['correo']),
                    'password' => Hash::make(User::DEFAULTPASS),
                    'area_id' => $this->user['area_id'] ?? null,
                ]);

                $user->information()->create([
                    'nombres' => $this->formatName($this->user['information']['nombres']),
                    'apellidos' => $this->formatName($this->user['information']['apellidos']),
                    'cui' => $this->user['information']['cui'],
                    'telefono' => $this->user['information']['telefono'],
                    'fecha_nacimiento' => $this->user['information']['fecha_nacimiento'],
                    'correo' => $this->normalizeEmail($this->user['information']['correo']),
                    'sexo' => $this->user['information']['sexo'],
                ]);

                $user->information->domicilio()->create([
                    'municipio_id' => $this->user['information']['domicilio']['municipio_id'],
                    'zona_id' => $this->user['information']['domicilio']['zona_id'] ?? null,
                    'colonia' => $this->normalizeOptionalText($this->user['information']['domicilio']['colonia']),
                    'direccion' => $this->formatName($this->user['information']['domicilio']['direccion']),
                ]);

                if (! empty($this->user['role'])) {
                    $user->syncRoles($this->user['role']);
                }

                Flux::toast(text: 'Usuario creado correctamente.', variant: 'success');
                $this->resetData();
            });
        } catch (\Throwable $th) {
            Flux::toast(text: 'Error al crear al usuario.', variant: 'danger');
        }
    }

    public function userRestore(int $id): void
    {
        $this->usuario_id = $id;
        Flux::modal('restaurar-usuario')->show();
    }

    public function restore(): void
    {
        $user = User::withTrashed()->findOrFail($this->usuario_id);
        $user->restore();

        Flux::toast(text: 'Usuario restaurado correctamente.', variant: 'success');
        Flux::modals()->close();
    }

    public function resetData(): void
    {
        $this->reset(['user', 'municipio_id']);
        Flux::modals()->close();
    }

    protected function syncMunicipioFromForm(): void
    {
        $this->user['information']['domicilio']['municipio_id'] = $this->municipio_id;
    }

    protected function formatName(?string $value): ?string
    {
        return ! empty($value)
            ? ucwords(mb_strtolower(trim($value)))
            : null;
    }

    protected function normalizeOptionalText(?string $value): ?string
    {
        return ! empty($value)
            ? $this->formatName($value)
            : null;
    }

    protected function normalizeEmail(?string $email): ?string
    {
        return ! empty($email)
            ? mb_strtolower(trim($email))
            : null;
    }
}
