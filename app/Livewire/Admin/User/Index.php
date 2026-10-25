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
use Livewire\Attributes\Title;

/** Componente Livewire para listar, crear y restaurar usuarios administrativos. */
#[Title('Usuarios')]
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

    /**
     * Lista usuarios, incluidos los desactivados, con información y área aplicando criterios de tabla.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    // Lista usuarios, incluidos los desactivados, con información y área aplicando criterios de tabla.
    //   Ejemplo: Desde Blade: {{ $this->rows->total() }}.
    //   Resultado: La tabla recibe la página filtrada y conserva el total para la paginación.
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

    /**
     * Carga departamentos, áreas y roles para los controles del formulario.
     *
     * @return void
     */
    // Carga departamentos, áreas y roles para los controles del formulario.
    //   Ejemplo: Livewire invoca este hook al montar el componente.
    //   Resultado: El componente queda autorizado y configurado con sus valores iniciales.
    public function mount(): void
    {
        $this->departamentos = Departamento::orderBy('nombre')->get();
        $this->areas = Area::orderBy('name')->get();
        $this->roles = Role::orderBy('name')->get();
    }

    /**
     * Define validaciones del alta para datos personales, contacto y domicilio.
     *
     * @return array
     */
    // Define validaciones del alta para datos personales, contacto y domicilio.
    //   Ejemplo: Livewire lo consulta al ejecutar $this->validate().
    //   Resultado: Los campos conformes pasan la validación y los demás reciben errores asociados.
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

    /**
     * Carga municipios pertenecientes al departamento seleccionado para la vista.
     *
     * @return \Illuminate\Contracts\View\View
     */
    // Carga municipios pertenecientes al departamento seleccionado para la vista.
    //   Ejemplo: Livewire lo invoca al mostrar o actualizar el componente.
    //   Resultado: Livewire entrega la vista con los datos preparados para sus controles.
    public function render()
    {
        $municipios = Municipio::where('departamento_id', $this->departamento_id)
            ->orderBy('nombre')
            ->get();

        return view('livewire.admin.user.index', compact('municipios'));
    }

    /**
     * Valida y crea usuario, información y domicilio en una transacción, asignando el rol opcional.
     *
     * @return void
     */
    // Valida y crea usuario, información y domicilio en una transacción, asignando el rol opcional.
    //   Ejemplo: Desde la vista: wire:click="store"
    //   Resultado: El registro se crea y la interfaz confirma el resultado.
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

    /**
     * Selecciona un usuario eliminado y abre la confirmación de restauración.
     *
     * @param  int $id
     * @return void
     */
    // Selecciona un usuario eliminado y abre la confirmación de restauración.
    //   Ejemplo: Desde la vista: wire:click="userRestore({{ $row->id }})"
    //   Resultado: Se abre el diálogo de restauración para el usuario seleccionado.
    public function userRestore(int $id): void
    {
        $this->usuario_id = $id;
        Flux::modal('restaurar-usuario')->show();
    }

    /**
     * Restaura el usuario seleccionado desde la papelera y cierra los modales.
     *
     * @return void
     */
    // Restaura el usuario seleccionado desde la papelera y cierra los modales.
    //   Ejemplo: Desde la vista: wire:click="restore"
    //   Resultado: El usuario vuelve a estar activo y se muestra una confirmación.
    public function restore(): void
    {
        $user = User::withTrashed()->findOrFail($this->usuario_id);
        $user->restore();

        Flux::toast(text: 'Usuario restaurado correctamente.', variant: 'success');
        Flux::modals()->close();
    }

    /**
     * Restablece los datos del formulario y cierra los modales.
     *
     * @return void
     */
    // Restablece los datos del formulario y cierra los modales.
    //   Ejemplo: Desde la vista: wire:click="resetData"
    //   Resultado: Los datos y errores del formulario se limpian y se cierran los modales.
    public function resetData(): void
    {
        $this->reset(['user', 'municipio_id']);
        Flux::modals()->close();
    }

    /**
     * Copia al domicilio el municipio seleccionado para validar y guardar la misma ubicación.
     *
     * @return void
     */
    // Copia al domicilio el municipio seleccionado para validar y guardar la misma ubicación.
    //   Ejemplo: Uso interno: $this->syncMunicipioFromForm()
    //   Resultado: El domicilio recibe el municipio actualmente seleccionado.
    protected function syncMunicipioFromForm(): void
    {
        $this->user['information']['domicilio']['municipio_id'] = $this->municipio_id;
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
     * Normaliza texto opcional como nombre y mantiene null si no se proporcionó.
     *
     * @param  ?string $value
     * @return ?string
     */
    // Normaliza texto opcional como nombre y mantiene null si no se proporcionó.
    //   Ejemplo: Uso interno: $this->normalizeOptionalText($value)
    //   Resultado: El texto opcional queda normalizado; una entrada vacía se convierte en null.
    protected function normalizeOptionalText(?string $value): ?string
    {
        return ! empty($value)
            ? $this->formatName($value)
            : null;
    }

    /**
     * Recorta espacios y convierte el correo a minúsculas antes de guardarlo.
     *
     * @param  ?string $email
     * @return ?string
     */
    // Recorta espacios y convierte el correo a minúsculas antes de guardarlo.
    //   Ejemplo: Uso interno: $this->normalizeEmail($email)
    //   Resultado: El correo queda recortado y en minúsculas.
    protected function normalizeEmail(?string $email): ?string
    {
        return ! empty($email)
            ? mb_strtolower(trim($email))
            : null;
    }
}
