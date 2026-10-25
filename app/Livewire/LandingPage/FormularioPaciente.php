<?php

namespace App\Livewire\LandingPage;

use App\Models\Cita;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Permite completar los datos de paciente asociados a una cita firmada.
 */
#[Title('Completar datos del paciente')]
class FormularioPaciente extends Component
{
    #[Locked]
    public int $citaId;

    public array $paciente = [];

    public bool $guardado = false;

    /**
     * Fija el ID de la cita recibida por ruta y precarga los datos del paciente.
     * @param int $cita Dato recibido por el componente.
     * @return void Fija el ID de la cita recibida por ruta y precarga los datos del paciente.
     */
    // Fija el ID de la cita recibida por ruta y precarga los datos del paciente.
    //   Ejemplo: abrir el enlace firmado asociado a la cita #15.
    //   Resultado: formulario muestra los datos existentes de ese expediente.
    public function mount(int $cita): void
    {
        $this->citaId = $cita;
        $this->cargarDatosPaciente();
    }

    /**
     * Valida y persiste los campos personales complementarios del paciente.
     * @return void Valida y persiste los campos personales complementarios del paciente.
     */
    // Valida y persiste los campos personales complementarios del paciente.
    //   Desde la vista: <flux:button wire:click="guardar">Guardar datos</flux:button>
    //   Resultado: paciente actualizado, indicador guardado activo y datos recargados.
    public function guardar(): void
    {
        $cita = $this->cita();
        $paciente = $cita->expediente->paciente;
        $datos = $this->validate([
            'paciente.nombres' => ['required', 'string', 'max:150'],
            'paciente.apellidos' => ['required', 'string', 'max:150'],
            'paciente.fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'paciente.sexo' => ['nullable', 'in:M,F,otro'],
            'paciente.estado_civil' => ['nullable', 'in:soltero,casado,divorciado,viudo,union_libre,otro'],
            'paciente.religion' => ['nullable', 'string', 'max:60'],
            'paciente.numero_dpi' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('pacientes', 'numero_dpi')->ignore($paciente->id),
            ],
            'paciente.telefono' => ['nullable', 'string', 'max:20'],
            'paciente.telefono_emergencia' => ['nullable', 'string', 'max:20'],
            'paciente.email' => ['nullable', 'email', 'max:191'],
            'paciente.direccion' => ['nullable', 'string'],
            'paciente.ocupacion' => ['nullable', 'string', 'max:100'],
        ]);

        $paciente->fill($datos['paciente']);
        $paciente->save();

        $this->guardado = true;
        $this->cargarDatosPaciente();
    }

    /**
     * Renderiza el formulario público usando el layout de la landing page.
     * @return \Illuminate\Contracts\View\View Renderiza el formulario público usando el layout de la landing page.
     */
    // Renderiza el formulario público usando el layout de la landing page.
    //   Ejemplo: visita al formulario de paciente desde su enlace firmado.
    //   Resultado: se muestra livewire.landing-page.formulario-paciente en el layout público.
    public function render()
    {
        return view('livewire.landing-page.formulario-paciente')
            ->layout('layouts.landing-page');
    }

    /**
     * Recupera la cita con expediente y paciente, o devuelve 404 si no existe.
     * @return Cita Recupera la cita con expediente y paciente, o devuelve 404 si no existe.
     */
    // Recupera la cita con expediente y paciente, o devuelve 404 si no existe.
    //   Ejemplo: $this->cita() durante la carga o el guardado del formulario.
    //   Resultado: instancia de Cita con relaciones necesarias precargadas.
    private function cita(): Cita
    {
        return Cita::query()
            ->with('expediente.paciente')
            ->findOrFail($this->citaId);
    }

    /**
     * Copia al formulario los campos editables y normaliza la fecha como Y-m-d.
     * @return void Copia al formulario los campos editables y normaliza la fecha como Y-m-d.
     */
    // Copia al formulario los campos editables y normaliza la fecha como Y-m-d.
    //   Ejemplo: se ejecuta al montar y después de persistir cambios.
    //   Resultado: propiedades del formulario sincronizadas con la información guardada.
    private function cargarDatosPaciente(): void
    {
        $this->paciente = $this->cita()->expediente->paciente->only([
            'nombres',
            'apellidos',
            'fecha_nacimiento',
            'sexo',
            'estado_civil',
            'religion',
            'numero_dpi',
            'telefono',
            'telefono_emergencia',
            'email',
            'direccion',
            'ocupacion',
        ]);

        $this->paciente['fecha_nacimiento'] = $this->paciente['fecha_nacimiento']?->format('Y-m-d');
    }
}
