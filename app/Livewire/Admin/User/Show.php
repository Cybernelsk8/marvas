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
use Livewire\Attributes\Title;

/** Componente Livewire para consultar y actualizar el perfil y los accesos de un usuario. */
#[Title('Detalles del usuario')]
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

    /**
     * Carga el usuario y sus relaciones, sincroniza el formulario y prepara permisos, departamentos y roles.
     *
     * @param  User $user
     * @return void
     */
    // Carga el usuario y sus relaciones, sincroniza el formulario y prepara permisos, departamentos y roles.
    //   Ejemplo: Al abrir la ruta /admin/users/3, Livewire recibe el usuario #3.
    //   Resultado: El componente queda autorizado y configurado con sus valores iniciales.
    public function mount(User $user): void
    {
        $this->user = $user->load(['roles', 'information.domicilio']);
        $this->syncFormState();
        $this->selectedRows = $this->user->getAllPermissions()->pluck('id')->toArray();
        $this->departamentos = Departamento::orderBy('nombre')->get();
        $this->roles = Role::orderBy('name')->get();
    }

    /**
     * Lista permisos con búsqueda, filtros y paginación para administrar accesos directos.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    // Lista permisos con búsqueda, filtros y paginación para administrar accesos directos.
    //   Ejemplo: Desde Blade: {{ $this->rows->total() }}.
    //   Resultado: La tabla recibe la página filtrada y conserva el total para la paginación.
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

    /**
     * Define las validaciones de perfil, rol, identidad, contacto y domicilio.
     *
     * @return array
     */
    // Define las validaciones de perfil, rol, identidad, contacto y domicilio.
    //   Ejemplo: Livewire lo consulta al ejecutar $this->validate().
    //   Resultado: Los campos conformes pasan la validación y los demás reciben errores asociados.
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

    /**
     * Carga municipios del departamento seleccionado y entrega la vista de detalle.
     *
     * @return \Illuminate\Contracts\View\View
     */
    // Carga municipios del departamento seleccionado y entrega la vista de detalle.
    //   Ejemplo: Livewire lo invoca al mostrar o actualizar el componente.
    //   Resultado: Livewire entrega la vista con los datos preparados para sus controles.
    public function render()
    {
        $municipios = Municipio::where('departamento_id', $this->departamento_id)
            ->orderBy('nombre')
            ->get();

        return view('livewire.admin.user.show', compact('municipios'));
    }

    /**
     * Valida y actualiza rol, información personal y domicilio dentro de una transacción.
     *
     * @return void
     */
    // Valida y actualiza rol, información personal y domicilio dentro de una transacción.
    //   Ejemplo: Desde la vista: wire:click="updateProfileInformation"
    //   Resultado: El perfil y sus relaciones quedan actualizados dentro de una transacción.
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

    /**
     * Restablece la contraseña a la clave predeterminada del modelo y notifica el resultado.
     *
     * @return void
     */
    // Restablece la contraseña a la clave predeterminada del modelo y notifica el resultado.
    //   Ejemplo: Desde la vista: wire:click="resetPassword"
    //   Resultado: La contraseña predeterminada se guarda cifrada y se confirma la acción.
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

    /**
     * Desactiva lógicamente la cuenta para conservar datos y relaciones.
     *
     * @return void
     */
    // Desactiva lógicamente la cuenta para conservar datos y relaciones.
    //   Ejemplo: Desde la vista: wire:click="disabledUser"
    //   Resultado: La cuenta queda desactivada lógicamente y se confirma la acción.
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

    /**
     * Valida y almacena la foto cargada y actualiza la ruta en la información del usuario.
     *
     * @return void
     */
    // Valida y almacena la foto cargada y actualiza la ruta en la información del usuario.
    //   Ejemplo: Desde la vista: wire:click="uploadPicture"
    //   Resultado: La ruta de la nueva foto queda guardada en el perfil.
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

    /**
     * Elimina el archivo de foto existente y limpia su ruta persistida.
     *
     * @return void
     */
    // Elimina el archivo de foto existente y limpia su ruta persistida.
    //   Ejemplo: Desde la vista: wire:click="deletePicture"
    //   Resultado: El archivo y su ruta asociada se eliminan cuando existe una foto.
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

    /**
     * Sincroniza en la cuenta los permisos seleccionados en la tabla.
     *
     * @return void
     */
    // Sincroniza en la cuenta los permisos seleccionados en la tabla.
    //   Ejemplo: Desde la vista: wire:click="syncDirectPermissions"
    //   Resultado: Los permisos directos reflejan la selección actual.
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

    /**
     * Cierra los modales abiertos tras completar o cancelar una operación.
     *
     * @return void
     */
    // Cierra los modales abiertos tras completar o cancelar una operación.
    //   Ejemplo: Desde la vista: wire:click="resetData"
    //   Resultado: Los datos y errores del formulario se limpian y se cierran los modales.
    public function resetData(): void
    {
        Flux::modals()->close();
    }

    /**
     * Copia datos persistidos al formulario y deriva el departamento desde el municipio del domicilio.
     *
     * @return void
     */
    // Copia datos persistidos al formulario y deriva el departamento desde el municipio del domicilio.
    //   Ejemplo: Uso interno: $this->syncFormState()
    //   Resultado: El formulario refleja los datos cargados del usuario y su departamento.
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

    /**
     * Recorta, pasa a minúsculas y capitaliza cada palabra para estandarizar nombres.
     *
     * @param  ?string $value
     * @return ?string
     */
    // Recorta, pasa a minúsculas y capitaliza cada palabra para estandarizar nombres.
    //   Ejemplo: Uso interno: $this->formatName($value)
    //   Resultado: "ana pérez" se transforma en "Ana Pérez"; una entrada vacía da null.
    protected function formatName(?string $value): ?string
    {
        return ! empty($value)
            ? ucwords(mb_strtolower(trim($value)))
            : null;
    }

    /**
     * Normaliza texto opcional con formatName y devuelve null si no hay contenido.
     *
     * @param  ?string $value
     * @return ?string
     */
    // Normaliza texto opcional con formatName y devuelve null si no hay contenido.
    //   Ejemplo: Uso interno: $this->normalizeOptionalText($value)
    //   Resultado: El texto opcional queda normalizado; una entrada vacía se convierte en null.
    protected function normalizeOptionalText(?string $value): ?string
    {
        return ! empty($value)
            ? $this->formatName($value)
            : null;
    }
}
