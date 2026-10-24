<?php

namespace App\Livewire\Admin\Clinical;

use App\Models\Admin\User;
use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Sucursal;
use App\Traits\DataTable;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profesionales')]
class Professionals extends Component
{
    use DataTable;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'align' => 'center'],
        ['index' => 'nombre_completo', 'label' => 'Profesional'],
        ['index' => 'user.email', 'label' => 'Cuenta'],
        ['index' => 'cedula_profesional', 'label' => 'Cédula'],
        ['index' => 'sucursales', 'label' => 'Sedes', 'exclude' => true],
        ['index' => 'especialidades', 'label' => 'Especialidades', 'exclude' => true],
        ['index' => 'activo', 'label' => 'Estado', 'exclude' => true],
        ['index' => 'actions', 'label' => '', 'width' => '100px'],
    ];

    public ?int $editingId = null;

    public string $userId = '';

    public string $nombreCompleto = '';

    public string $cedulaProfesional = '';

    public string $colorAgendaHex = '';

    public bool $activo = true;

    public array $branchIds = [];

    public array $specialtyIds = [];

    public ?string $primaryBranchId = null;

    public ?string $primarySpecialtyId = null;

    public function mount(): void
    {
        $this->authorize('clinical-catalogs.manage');
        $this->sortBy = 'nombre_completo';
        $this->sortDirection = 'asc';
    }

    #[Computed]
    public function rows()
    {
        return Profesional::query()
            ->with(['user', 'sucursales', 'especialidades'])
            ->filterAdvance($this->headers, [
                'search' => $this->search,
                'sort' => ['field' => $this->sortBy, 'direction' => $this->sortDirection],
                'filters' => $this->processFilters(),
            ])
            ->paginate($this->per_page ?? 10);
    }

    public function updatedUserId(?string $userId): void
    {
        if (! $userId) {
            return;
        }

        $user = User::query()->findOrFail($userId);
        if (blank($this->nombreCompleto)) {
            $this->nombreCompleto = $user->name;
        }
    }

    public function updatedBranchIds(): void
    {
        if (! in_array($this->primaryBranchId, $this->branchIds, true)) {
            $this->primaryBranchId = $this->branchIds[0] ?? null;
        }
    }

    public function updatedSpecialtyIds(): void
    {
        if (! in_array($this->primarySpecialtyId, $this->specialtyIds, true)) {
            $this->primarySpecialtyId = $this->specialtyIds[0] ?? null;
        }
    }

    public function save(): void
    {
        $this->authorize('clinical-catalogs.manage');
        $this->validate([
            'userId' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                Rule::unique('profesionales', 'user_id')->ignore($this->editingId),
            ],
            'nombreCompleto' => ['required', 'string', 'max:200'],
            'cedulaProfesional' => ['nullable', 'string', 'max:60'],
            'colorAgendaHex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'activo' => ['required', 'boolean'],
            'branchIds' => ['required', 'array', 'min:1'],
            'branchIds.*' => ['required', 'integer', 'distinct', 'exists:sucursales,id,activa,1'],
            'specialtyIds' => ['required', 'array', 'min:1'],
            'specialtyIds.*' => ['required', 'integer', 'distinct', 'exists:especialidades,id,activa,1'],
            'primaryBranchId' => ['required', 'integer', Rule::in($this->branchIds)],
            'primarySpecialtyId' => ['required', 'integer', Rule::in($this->specialtyIds)],
        ]);

        DB::transaction(function (): void {
            $professional = $this->editingId
                ? Profesional::query()->lockForUpdate()->findOrFail($this->editingId)
                : new Profesional;
            $professional->fill([
                'user_id' => (int) $this->userId,
                'nombre_completo' => trim($this->nombreCompleto),
                'cedula_profesional' => filled($this->cedulaProfesional) ? trim($this->cedulaProfesional) : null,
                'color_agenda_hex' => $this->colorAgendaHex ?: null,
                'activo' => $this->activo,
            ])->save();

            $professional->sucursales()->sync(collect($this->branchIds)->mapWithKeys(
                fn (string|int $id): array => [(int) $id => ['es_principal' => (int) $id === (int) $this->primaryBranchId]],
            )->all());
            $professional->especialidades()->sync(collect($this->specialtyIds)->mapWithKeys(
                fn (string|int $id): array => [(int) $id => ['es_principal' => (int) $id === (int) $this->primarySpecialtyId]],
            )->all());
        });

        Flux::modal('professional-form')->close();
        Flux::toast(text: $this->editingId ? 'Profesional actualizado.' : 'Profesional creado.', variant: 'success');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorize('clinical-catalogs.manage');
        $professional = Profesional::query()->with(['sucursales', 'especialidades'])->findOrFail($id);
        $this->editingId = $professional->id;
        $this->userId = (string) $professional->user_id;
        $this->nombreCompleto = $professional->nombre_completo;
        $this->cedulaProfesional = $professional->cedula_profesional ?? '';
        $this->colorAgendaHex = $professional->color_agenda_hex ?? '';
        $this->activo = $professional->activo;
        $this->branchIds = $professional->sucursales->pluck('id')->map(fn (int $id): string => (string) $id)->all();
        $this->specialtyIds = $professional->especialidades->pluck('id')->map(fn (int $id): string => (string) $id)->all();
        $this->primaryBranchId = (string) ($professional->sucursalPrincipal()?->id ?? '');
        $this->primarySpecialtyId = (string) ($professional->especialidadPrincipal()?->id ?? '');
        Flux::modal('professional-form')->show();
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('clinical-catalogs.manage');
        $professional = Profesional::query()->findOrFail($id);
        $professional->update(['activo' => ! $professional->activo]);
        Flux::toast(text: $professional->activo ? 'Profesional activado.' : 'Profesional desactivado.', variant: 'success');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'userId',
            'nombreCompleto',
            'cedulaProfesional',
            'colorAgendaHex',
            'activo',
            'branchIds',
            'specialtyIds',
            'primaryBranchId',
            'primarySpecialtyId',
        ]);
        $this->resetValidation();
    }

    public function render()
    {
        $users = User::query()
            ->whereDoesntHave('profesional')
            ->orderBy('email')
            ->get(['id', 'name', 'email']);
        if ($this->editingId && $this->userId) {
            $users->push(User::query()->findOrFail($this->userId));
        }

        return view('livewire.admin.clinical.professionals', [
            'users' => $users->unique('id')->values(),
            'branches' => Sucursal::query()->where('activa', true)->orderBy('nombre')->get(['id', 'nombre']),
            'specialties' => Especialidad::query()->where('activa', true)->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }
}
