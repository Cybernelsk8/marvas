<?php

namespace App\Livewire\Admin\User;

use App\Models\Admin\Domicilio;
use App\Models\Admin\Permission;
use App\Models\Admin\User;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Traits\DataTable;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

class Show extends Component
{
    use WithFileUploads, DataTable;

    public array $headers = [
        ['index' => 'id', 'label' => '#', 'align' => 'center'],
        ['index' => 'name', 'label' => 'Permiso'],
    ];

    public User $user;

    public array $usuario = [
        'role' => null,
        'information' => [
            'id' => null,
            'nombres' => null,
            'apellidos' => null,
            'cui' => null,
            'telefono' => null,
            'fecha_nacimiento' => null,
            'correo' => null,
            'sexo' => null,
            'foto' => null,
            'domicilio' => [
                'id' => null,
                'municipio_id' => null,
                'zona_id' => null,
                'colonia' => null,
                'direccion' => null,
            ],
        ],
    ];

    public $departamentos, $roles;

    public ?int $departamento_id = null;

    public ?string $search_permissions = null;

    public function mount(User $user): void
    {
        $this->user = $user->load(['roles', 'information.domicilio']);
        $this->syncFormState();
        $this->selectedRows = $this->user->getAllPermissions()->pluck('id')->toArray();
        $this->departamentos = Departamento::orderBy('nombre')->get();
        $this->roles = Role::orderBy('name')->get();
    }

    #[Computed]
    public function rows()
    {
        $query = Permission::filterAdvance($this->headers, [
            'search' => $this->search,
            'sort' => [
                'field' => $this->sortBy,
                'direction' => $this->sortDirection,
            ],
            'filters' => $this->processFilters(),
        ]);

        return $query->paginate($this->per_page);
    }

    public function rules(): array
    {
        return [
            'usuario.role' => 'nullable|exists:roles,name',
            'usuario.information.nombres' => 'required|string|max:255',
            'usuario.information.apellidos' => 'required|string|max:255',
            'usuario.information.cui' => 'required|digits:13|unique:user_information,cui,' . ($this->usuario['information']['id'] ?? 'NULL') . ',id',
            'usuario.information.telefono' => 'required|max:9|regex:/^\d{4}-\d{4}$/',
            'usuario.information.fecha_nacimiento' => 'required|date|date_format:Y-m-d',
            'usuario.information.correo' => 'required|string|email',
            'usuario.information.sexo' => 'required|in:M,F',
            'usuario.information.domicilio.municipio_id' => 'required|exists:municipios,id',
            'usuario.information.domicilio.zona_id' => 'nullable|exists:zonas,id',
            'usuario.information.domicilio.colonia' => 'nullable|string|max:255',
            'usuario.information.domicilio.direccion' => 'required|string|max:255',
        ];
    }

    public function render()
    {
        $municipios = Municipio::where('departamento_id', $this->departamento_id)
            ->orderBy('nombre')
            ->get();

        return view('livewire.admin.user.show', compact('municipios'));
    }

    public function updateProfileInformation(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $role = $this->usuario['role'];

                if ($role) {
                    $this->user->syncRoles([$role]);
                }

                $information = $this->user->information;
                $information->fill([
                    'nombres' => $this->formatName($this->usuario['information']['nombres']),
                    'apellidos' => $this->formatName($this->usuario['information']['apellidos']),
                    'cui' => $this->usuario['information']['cui'],
                    'telefono' => $this->usuario['information']['telefono'],
                    'fecha_nacimiento' => $this->usuario['information']['fecha_nacimiento'],
                    'correo' => mb_strtolower($this->usuario['information']['correo']),
                    'sexo' => $this->usuario['information']['sexo'],
                ]);

                $information->save();

                $domicilio = $information->domicilio ?? new Domicilio();
                $domicilio->fill([
                    'municipio_id' => $this->usuario['information']['domicilio']['municipio_id'],
                    'zona_id' => $this->usuario['information']['domicilio']['zona_id'] ?? null,
                    'colonia' => $this->normalizeOptionalText($this->usuario['information']['domicilio']['colonia']),
                    'direccion' => $this->formatName($this->usuario['information']['domicilio']['direccion']),
                ]);

                $domicilio->user_information_id = $information->id;
                $domicilio->save();

                $this->user->refresh();
                $this->syncFormState();
            });

            Flux::toast(text: 'Información actualizada correctamente.', variant: 'success');
        } catch (\Throwable $th) {
            Flux::toast(text: 'Ocurrió un error al actualizar la información. ' . $th->getMessage(), variant: 'danger');
        }
    }

    public function resetPassword(): void
    {
        try {
            $this->user->password = Hash::make($this->user::DEFAULTPASS);
            $this->user->save();

            Flux::toast(text: 'Se ha restablecido la contraseña al usuario.', variant: 'success');
            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Ocurrió un error al restablecer la contraseña.', variant: 'danger');
        }
    }

    public function disabledUser(): void
    {
        try {
            $this->user->delete();

            Flux::toast(text: 'Se ha desactivado al usuario.', variant: 'success');
            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Ocurrió un error al desactivar al usuario.', variant: 'danger');
        }
    }

    public function uploadPicture(): void
    {
        $this->validate([
            'usuario.information.foto' => 'nullable|image|max:2048',
        ]);

        try {
            $uploadedFile = $this->usuario['information']['foto'] ?? null;

            if ($uploadedFile) {
                $path = $uploadedFile->store('user-photos');
                $this->user->information->foto = $path;
                $this->user->information->save();

                Flux::toast(text: 'Foto de perfil actualizada correctamente.', variant: 'success');
            }
        } catch (\Throwable $th) {
            Flux::toast(text: 'Ocurrió un error al subir la foto de perfil.', variant: 'danger');
        }
    }

    public function deletePicture(): void
    {
        try {
            if (! empty($this->user->information->foto)) {
                Storage::delete($this->user->information->foto);
                $this->user->information->foto = null;
                $this->user->information->save();
            }

            Flux::toast(text: 'Foto de perfil eliminada correctamente.', variant: 'success');
        } catch (\Throwable $th) {
            Flux::toast(text: 'Ocurrió un error al eliminar la foto de perfil.', variant: 'danger');
        }
    }

    public function syncDirectPermissions(): void
    {
        try {
            $this->user->permissions()->sync($this->selectedRows);
            Flux::toast(text: 'Permisos asignados correctamente.', variant: 'success');
            $this->resetData();
        } catch (\Throwable $th) {
            Flux::toast(text: 'Ocurrió un error al asignar los permisos.', variant: 'danger');
        }
    }

    public function resetData(): void
    {
        Flux::modals()->close();
    }

    protected function syncFormState(): void
    {
        $information = $this->user->information;
        $domicilio = $information?->domicilio;
        $municipioId = $domicilio?->municipio_id;

        $this->usuario = [
            'role' => $this->user->roles->first()?->name,
            'information' => [
                'id' => $information?->id,
                'nombres' => $information?->nombres,
                'apellidos' => $information?->apellidos,
                'cui' => $information?->cui,
                'telefono' => $information?->telefono,
                'fecha_nacimiento' => $information?->fecha_nacimiento,
                'correo' => $information?->correo,
                'sexo' => $information?->sexo,
                'foto' => null,
                'domicilio' => [
                    'id' => $domicilio?->id,
                    'municipio_id' => $municipioId,
                    'zona_id' => $domicilio?->zona_id,
                    'colonia' => $domicilio?->colonia,
                    'direccion' => $domicilio?->direccion,
                ],
            ],
        ];

        $this->departamento_id = $municipioId
            ? (Municipio::find($municipioId)?->departamento_id ?? 7)
            : 7;
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
}
