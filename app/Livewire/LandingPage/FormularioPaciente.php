<?php

namespace App\Livewire\LandingPage;

use App\Models\Cita;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Completar datos del paciente')]
class FormularioPaciente extends Component
{
    #[Locked]
    public int $citaId;

    public array $paciente = [];

    public bool $guardado = false;

    public function mount(int $cita): void
    {
        $this->citaId = $cita;
        $this->cargarDatosPaciente();
    }

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

    public function render()
    {
        return view('livewire.landing-page.formulario-paciente')
            ->layout('layouts.landing-page');
    }

    private function cita(): Cita
    {
        return Cita::query()
            ->with('expediente.paciente')
            ->findOrFail($this->citaId);
    }

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
